<?php

namespace Com\Log;

use Zend\Log\Logger as zLogger;

class Logger extends zLogger
{

    protected $enabled = true;


    function setEnabled($value)
    {
        $this->enabled = (bool)$value;
        return $this;
    }


    function getEnabled()
    {
        return $this->enabled;
    }


    /**
     * @param string $priority
     * @param string $message
     * @param array|Traversable $extra
     * @param string $ref
     * @return Logger
     */
    public function log($priority, $message, $extra = [], $ref = null)
    {
        $force = true;

        if(!$this->enabled)
        {
            if((self::DEBUG == $priority))
            {
                return $this;
            }
        }

        if(!is_array($extra))
        {
            $extra = array($extra);
        }

        if(is_object($message))
        {
            if($message instanceof \exception)
            {
                $extra['exception_message'] = $message->getMessage();
                $extra['exception_line'] = $message->getLine();
                $extra['exception_file'] = $message->getFile();
                $extra['exception_code'] = $message->getCode();
                $extra['exception_message'] = $message->getMessage();

                $extra['exception'] = $message->getTraceAsString();
                $message = "Exception: {$message->getMessage()}";
            }
            else
            {
                if(method_exists($message, 'toArray()'))
                {
                    $extra[] = $message->toArray();
                    $message = 'Class: ' . get_class($message);
                }
                elseif(!method_exists($message, '__toString'))
                {
                    $extra[] = (string)$message;
                    $message = 'Class: ' . get_class($message);
                }
            }
        }

        if(strlen($message) > 250)
        {
            $message = substr($message, 0, 250-1);
        }

        if(!empty($ref))
        {
            $extra['_ref_'] = $ref;
        }

        return parent::log($priority, $message, $extra);
    }


    /**
     * @param string $message
     * @param array|Traversable $extra
     * @param string $ref
     * @return Logger
     */
    public function debug($message, $extra = [], $ref = null)
    {
        return $this->log(self::DEBUG, $message, $extra, $ref);
    }


    /**
     * @param string $message
     * @param array|Traversable $extra
     * @param string $ref
     * @return Logger
     */
    public function error($message, $extra = [], $ref = null)
    {
        return $this->log(self::ERR, $message, $extra, $ref);
    }
    

    /**
     * @param string $message
     * @param array|Traversable $extra
     * @param string $ref
     * @return Logger
     */
    public function warning($message, $extra = [], $ref = null)
    {
        return $this->log(self::WARN, $message, $extra, $ref);
    }
}