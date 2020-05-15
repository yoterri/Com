<?php

namespace Com\Validator;

use Laminas\Validator\Callback as zCallback;

class Callback extends zCallback
{   

    protected $params = array();


    /**
     * @param array $data
     */
    function setParams($data)
    {
        foreach($data as $key => $value)
        {
            $this->setParam($key, $value);
        }

        return $this;
    }

    /**
     * @param string $key
     * @param string $value
     */
    function setParam($key, $value)
    {
        $this->params[$key] = $value;
        return $this;
    }


    /**
     * @param string $key
     * @param string $default
     */
    function getParam($key, $default = null)
    {
        if($this->hasParam($key))
        {
            $default = $this->params[$key];
        }

        return $default;
    }


    /**
     * @param string $key
     */
    function hasParam($key)
    {
        return isset($this->params[$key]);
    }




    public function isValid($value)
    {
        $context = $this;
        return parent::isValid($value, $context);
    }


    /**
     * @param string $message - set the custom error message
     */
    function setError($message)
    {
        $this->setMessage($message, self::INVALID_VALUE);
        return $this;
    }
}