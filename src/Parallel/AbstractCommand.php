<?php

namespace Com\Parallel;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

use Com\Interfaces\LazyLoadInterface;
use Com\Traits\ContainerAwareTrait;
use Com\Interfaces\ContainerAwareInterface;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\Stdlib\Parameters;

use Zend\Log\Logger;
use Zend\Log\Formatter\Simple;
use Zend\Log\Writer\Stream;
use Zend\Log\Writer\Noop;

Abstract class AbstractCommand extends Command implements EventManagerAwareInterface, ContainerAwareInterface, LazyLoadInterface
{
    use EventManagerAwareTrait, ContainerAwareTrait;

    /**
     * @var string
     */
    protected $dbInstanceClass = 'Com\Parallel\Db\Instance';

    /**
     * @var string
     */
    protected $dbItemClass = 'Com\Parallel\Db\Item';

    /**
     * @var string|array
     */
    protected $rowIdentifier = 'id';

    /**
     * @var string
     */
    protected $phpPath = 'php';

    /**
     * @var string
     */
    protected $logPath = '';




    ################

    /**
     * @var InputInterface
     */
    protected $logger;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var int
     */
    protected $instance = null;

    /**
     * @var string
     */
    private $scriptPath;

    /**
     * @var Com\Db\AbstractDb
     */
    protected $dbInstance;

    /**
     * @var Com\Db\AbstractDb
     */
    protected $dbItem;



    function __construct()
    {
        parent::__construct();
        set_error_handler(array($this, 'error_handler'));
        set_exception_handler(array($this, 'exception_handler'));
    }
    

    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->input = $input;
        $instance = null;

        $this->_createLogger();

        #
        if($input->hasArgument('instance'))
        {
            $instance = $input->getArgument('instance');

            if(!is_null($instance))
            {
                if(!is_numeric($instance) || (abs((int)$instance) != $instance) || (0 == $instance))
                {
                    throw new \Exception('Error Processing Request. Invalid parameter type');
                }
            }
        }

        $phpFile = $_SERVER['argv'][0];
        $command = $_SERVER['argv'][1];
        $this->scriptPath = "{$phpFile} {$command}";

        #
        $sm = $this->getContainer();
        $this->dbInstance = $sm->get($this->dbInstanceClass);
        $this->dbItem = $sm->get($this->dbItemClass);

        if($instance)
        {
            $this->instance = (int)$instance;
            $this->_runInstance();
        }
        else
        {
            $this->_prepareInstances();
        }
    }


    function process($row)
    {
        ;
    }


    function onProcessed($row, $instanceRow, $itemRow)
    {
        ;
    }


    function getRowset()
    {
        return [];
    }


    protected function _createLogger()
    {
        $this->logger = new Logger();

        if($this->logPath)
        {
            $logSeparator = str_repeat('-', 70) . PHP_EOL;

            $format = sprintf('%%timestamp%% %%priorityName%% (%%priority%%): %s%%message%%  %s%%extra%% %s', PHP_EOL.PHP_EOL, PHP_EOL.PHP_EOL, PHP_EOL);
            $formatter = new Simple($format);

            $writer1  = new Stream($this->logPath, NULL, $logSeparator);
            $writer1->setFormatter($formatter);

            $this->logger->addWriter($writer1);
        }
        else
        {
            $this->logger->addWriter(new Noop());
        }
    }


    function log($message, $type = 'debug')
    {
        $this->logger->$type($message);
    }
 

    protected function _prepareInstances()
    {
        $rowset = $this->getRowset();

        $total = number_format(count($rowset));
        $this->log("There is a total of {$total} rows to process.");

        #
        foreach($rowset as $number => $rowData) {
            $instanceNumber = ($number + 1);

            $cmd = "{$this->phpPath} {$this->scriptPath} {$instanceNumber} >/dev/null & ";
            $running = $this->_instanceIsRunning($instanceNumber);

            $logMessage = "Preparing command '$cmd' - ";

            if(!$running) 
            {
                $this->_createInstance($instanceNumber, $rowData);
                $cmd = "{$this->phpPath} {$this->scriptPath} {$instanceNumber} >/dev/null & ";
                exec($cmd);

                $logMessage .= 'SUCCESS';
            }
            else
            {
                $logMessage .= 'Already running';
            }

            $this->log($logMessage);
        }
    }



    protected function _runInstance()
    {
        $sm = $this->getContainer();        

        #
        $where = [
            'number' => $this->instance,
            'script' => $this->scriptPath,
        ];
        $instanceRow = $this->dbInstance->findBy($where)->current();

        if(!$instanceRow)
        {
            return;
        }

        $itemRow = $this->dbItem->findBy(function($where) use($instanceRow) {
            $where->equalTo('instance_id', $instanceRow->id);
            $where->isNull('finished_on');
        })->current();

        if(!$itemRow)
        {
            $message = "Unable to find item_row. { number:{$this->instance},script:{$this->scriptPath} }";
            $this->log($message, 'err');
            return;
        }

        $data = null;
        if($itemRow->data) 
        {
            if(file_exists($itemRow->data))
            {
                $content = file_get_contents($itemRow->data);
                $data = @json_decode($content, true);
                unlink($itemRow->data);
            }
        }

        try
        {
            $this->process($data);

            $up = ['finished_on' => date('Y-m-d H:i:s')];
            $this->dbItem->doUpdate($up, ['id' => $itemRow->id]);

            $this->onProcessed($data, $instanceRow, $itemRow);
        }
        catch(\Exception $ex)
        {
            $up = ['error' => $ex->getTraceAsString()];
            $this->dbItem->doUpdate($up, ['id' => $itemRow->id]);

            #
            $arr = array(
                'instance' => $this->instance,
                'script' => $this->scriptPath,
                'message' => $ex->getMessage(),
                'line' => $ex->getLine(),
                'file' => $ex->getFile(),
                'code' => $ex->getCode(),
                'exception' => $ex->getTraceAsString(),
            );

            #
            $message = print_r($arr, 1);
            $this->log($message, 'err');
        }
    }




    protected function _createInstance($instanceNumber, $rowData)
    {
        $dataFile = $this->_createFile($rowData);

        #
        $where = ['number' => $instanceNumber, 'script' => $this->scriptPath];
        $instance = $this->dbInstance->findBy($where)->current();
        if(!$instance)
        {
            $data = [
                'number' => $instanceNumber,
                'script' => $this->scriptPath,
            ];
            $instanceId = $this->dbInstance->doInsert($data);
        }
        else
        {
            $instanceId = $instance->id;
        }

        #
        $in = [
            'started_on' => date('Y-m-d H:i:s'),
            'data' => $dataFile,
            'instance_id' => $instanceId,
        ];

        #
        if($this->rowIdentifier)
        {
            if(!is_array($this->rowIdentifier)) 
            {
                $in['item_id'] = $rowData[$this->rowIdentifier];
            }
            else
            {
                $itemId = [];
                foreach($this->rowIdentifier as $key)
                {
                    $itemId[$key] = $rowData[$key];
                }

                if($itemId)
                {
                    $in['item_id'] = json_encode($itemId);
                }
            }
        }

        $this->dbItem->doInsert($in);
    }



    protected function _createFile($rowData)
    {
        #
        if (!is_array($rowData))
        {
            if(is_object($rowData) && method_exists($rowData, 'toArray') )
            {
                $rowData = $rowData->toArray();
            } 
        }

        $data = json_encode($rowData);

        #
        $dir = sys_get_temp_dir();
        $pregix = 'parallel_' . uniqid() . '_';
        $file = tempnam($dir, $pregix);

        file_put_contents($file, $data);

        return $file;
   }


    protected function _instanceIsRunning($instanceNumber)
    {
        if(!$this->dbItem)
        {
            return false;
        }

        if(!$this->dbInstance)
        {
            return false;
        }


        $where = ['number' => $instanceNumber, 'script' => $this->scriptPath];
        $row = $this->dbInstance->findBy($where)->current();
        if(!$row)
        {
            return false;
        }

        #
        $count = $this->dbItem->count(function($where) use($row) {
            $where->isNull('finished_on');
            $where->equalTo('instance_id', $row->id);
        });

        #
        return ($count > 0) ? true : false;
    }


    protected function configure()
    {
        #
        {
            $class = get_class($this);
            $exploded = explode('\\', $class);
            $className = end($exploded);
            $commandName = (substr($className, 0, -7));
            $exploded = $this->_splitAtUpperCase($commandName);
            $this->commandName = strtolower(implode('_', $exploded));

            $this->setName(strtolower($this->commandName));
        }

        #
        $this->setDefinition(
            new InputDefinition(array(
                new InputOption('instance', 'i', InputOption::VALUE_OPTIONAL),
            ))
        );

        #
        $this->addArgument('instance', InputArgument::OPTIONAL);
    }


    protected function _splitAtUpperCase($s)
    {
        return preg_split('/(?=[A-Z])/', $s, -1, PREG_SPLIT_NO_EMPTY);
    }



    function error_handler($errno, $errstr, $errfile, $errline)
    {
        $arr = array(
            'number' => $errno,
            'errstr' => $errstr,
            'errfile' => $errfile,
            'errline' => $errline,
        );

        $err = "{$errno}: {$errstr} - {$errfile}({$errline})";

        throw new \Exception($err, $errno);
    }


    function exception_handler($ex)
    {
        $arr = array(
            'instance' => $this->instance,
            'script' => $this->scriptPath,
            'message' => $ex->getMessage(),
            'line' => $ex->getLine(),
            'file' => $ex->getFile(),
            'code' => $ex->getCode(),
            'exception' => $ex->getTraceAsString(),
        );

        #
        $message = print_r($arr, 1);
        
        $this->log($message, 'err');
    }
}