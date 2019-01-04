<?php

namespace Com\Log\Writer;

use Traversable;
use Zend\Db\Adapter\Adapter;
use Zend\Log\Exception;
use Zend\Log\Formatter\Db as DbFormatter;
use Zend\Log\Writer\AbstractWriter;
use Com\Db\AbstractDb;

class Db extends AbstractWriter
{
    
    /**
     * AbstractDb
     */
    protected $dbTable;

    protected $lastId = -1;


    /**
     * Constructor
     *
     * @param  $dbTable
     */
    public function __construct($dbTable = null)
    {
        if($dbTable)
        {
            $this->setDbTable($dbTable);
        }
    }


    function getLastId()
    {
        return $this->getLastId;
    }


    function setDbTable(AbstractDb $dbTable)
    {
        $this->dbTable = $dbTable;
        return $this;
    }

    
    function getDbTable()
    {
        return $this->dbTable;
    }

    /**
     * Remove reference to database adapter
     *
     * @return void
     */
    public function shutdown()
    {
        $this->dbTable = null;
    }

    /**
     * Write a message to the log.
     *
     * @param array $event event data
     * @return void
     * @throws Exception\RuntimeException
     */
    protected function doWrite(array $event)
    {
        if(!$this->dbTable)
        {
            throw new \Exception('Db Table is not set');
        }

        if($this->hasFormatter())
        {
            $event = $this->formatter->format($event);
        }

        $time = $event['timestamp'];

        $tz = $time->getTimezone();
        $tzTranslations = current($tz->getTransitions());

        $date = $time->format('Y-m-d H:i:s');
        $offset = (string)$tzTranslations['offset'];
        $tzName = $tz->getName();

        $type = $event['priorityName'];

        $in = array(
            'type' => $type,
            'created_on' => $date,
            'timezone' => "$tzName: $offset",
            'message' => $event['message'],
        );


        if(isset($event['extra']))
        {
            if(isset($event['extra']['_ref_']))
            {
                $ref = $event['extra']['_ref_'];

                unset($event['extra']['_ref_']);

                $in['ref'] = $ref;
            }

            if(!count($event['extra']))
            {
                unset($event['extra']);
            }
            else
            {
                $in['extra'] = print_r($event['extra'], 1);
            }
        }

        $body = file_get_contents('php://input');
        if(!empty($body))
        {
            $in['body'] = $body;
        }

        $headers = $this->_getHeaders();
        if($headers)
        {
            $in['headers'] = print_r($headers, 1);
        }
        
        if(isset($_SERVER['REQUEST_URI']))
        {
            $in['url'] = $_SERVER['REQUEST_URI'];
        }

        if(isset($_SERVER['HTTP_USER_AGENT']))
        {
            $in['agent'] = $_SERVER['HTTP_USER_AGENT'];
        }            

        if(isset($_SERVER['REMOTE_ADDR']))
        {
            $in['ip_address'] = $_SERVER['REMOTE_ADDR'];
        }            

        if(isset($_SERVER['REQUEST_METHOD']))
        {
            $in['method'] = $_SERVER['REQUEST_METHOD'];
        }

        if(isset($_GET) && count($_GET))
        {
            $in['get'] = print_r($_GET, 1);
        }

        if(isset($_FILES) && count($_FILES))
        {
            $in['file'] = print_r($_FILES, 1);
        }

        if(isset($_SESSION) && count($_SESSION))
        {
            $in['session'] = print_r($_SESSION, 1);
        }

        if(isset($_COOKIE) && count($_COOKIE))
        {
            $in['cookies'] = print_r($_COOKIE, 1);
        }

        if(isset($_POST) && count($_POST))
        {
            $in['post'] = print_r($_POST, 1);
        }

        $this->getLastId = $this->dbTable->doInsert($in);
    }



    /**
     * get all headers
     */
    protected function _getHeaders()
    {
        $headers = array();

        if(!function_exists('getallheaders'))
        {
            $copy_server = array(
                'CONTENT_TYPE'   => 'Content-Type',
                'CONTENT_LENGTH' => 'Content-Length',
                'CONTENT_MD5'    => 'Content-Md5',
            );


            foreach($_SERVER as $key => $value)
            {
                if(substr($key, 0, 5) === 'HTTP_')
                {
                    $key = substr($key, 5);
                    if(!isset($copy_server[$key]) || !isset($_SERVER[$key]))
                    {
                        $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $key))));
                        $headers[$key] = $value;
                    }
                }
                elseif(isset($copy_server[$key]))
                {
                    $headers[$copy_server[$key]] = $value;
                }
            }

            if(!isset($headers['Authorization']))
            {
                if(isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']))
                {
                    $headers['Authorization'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
                }
                elseif(isset($_SERVER['PHP_AUTH_USER']))
                {
                    $basic_pass = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
                    $headers['Authorization'] = 'Basic ' . base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $basic_pass);
                }
                elseif(isset($_SERVER['PHP_AUTH_DIGEST']))
                {
                    $headers['Authorization'] = $_SERVER['PHP_AUTH_DIGEST'];
                }
            }
        }
        else
        {
            $headers = \getallheaders();
        }

        return $headers;
    }
}
