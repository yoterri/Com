<?php
namespace Com;

class Communicator
{

    /**
     *
     * @var bool
     */
    protected $success = true;

    /**
     *
     * @var string
     */
    protected $message = null;

    /**
     *
     * @var array
     */
    protected $data = array();

    /**
     *
     * @var array
     */
    protected $errors = array();


    /**
     *
     * @return bool
     *
     */
    function isSuccess()
    {
        return $this->success;
    }

    /**
     * @param string $message
     * @return Communicator
     */
    function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }


    /**
     * @return string
     */
    function getMessage()
    {
        return $this->message;
    }
    

    /**
     * @param string $message
     * @param array $data
     * @return Communicator
     */
    function setSuccess($message = null, array $data = array())
    {
        $this->clearErrors();

        $this->success = true;
        $this->setMessage($message);
        $this->setData($data);

        return $this;
    }


    /**
     * @param \Exception $e
     * return Communicator 
     */
    function setException(\Exception $e)
    {
       $this->addError($e->getMessage());
       return $this;
    }

    /**
     *
     * @param string $message
     * @param string $key
     * @return Communicator
     */
    function addError($message, $key = null)
    {
        $this->success = false;
        $this->message = null;

        $message = (string)$message;

        if(!empty($key))
        {
            if(! isset($this->errors[$key]))
            {
                $this->errors[$key] = array();
            }

            $this->errors[$key][] = $message;
        }
        else
        {
            array_push($this->errors, $message);
        }

        return $this;
    }


    /**
     * Get all the error messages
     *
     * @example array(
     * 0 => 'Message 1',
     * 1 => 'Message 2',
     * 'key_1' => array(
     * 'messaje #1', 'message #2'
     * ),
     * 'key_2' => array(
     * 'messaje #3', 'message #4'
     * ),
     * )
     * @return array
     */
    function getErrors()
    {
        return $this->errors;
    }


    /**
     *
     * @return mixed
     */    
    function getError($key)
    {
        return $this->errors[$key];
    }


    /**
     *
     * @return array
     */
    function getGlobalErrors()
    {
        $r = array();

        $errors = $this->getErrors();
        foreach($errors as $key => $item)
        {
            if(is_numeric($key) && !is_array($item))
            {
                $r[] = $item;
            }
        }

        return $r;
    }


    /**
     * @return array
     */
    function getFieldErrors($fieldName = null)
    {
        $r = array();
        $errors = $this->getErrors();

        if(!empty($fieldName))
        {
            if(isset($errors[$fieldName]))
            {
                $r[] = $errors[$fieldName];
            }
        }
        else
        {
            foreach($errors as $key => $item)
            {
                if(is_array($item))
                {
                    $r[$key] = $item;
                }
            }
        }

        return $r;
    }


    /**
     *
     * @return Communicator
     */
    function clearErrors()
    {
        $this->errors = array();
        return $this;
    }


    /**
     * @param mixed $key
     * @param mixed $default
     * @return array | mixed
     */
    function getData($key = null, $default = null)
    {
        if(!empty($key))
        {
            $r = $default;
            if(isset($this->data[$key]))
            {
                $r = $this->data[$key];
            }
            
            return $r;
        }
        else
        {
            return $this->data;
        }
    }


    /**
     *
     * @return Communicator
     */
    function clearData()
    {
        $this->data = array();
        return $this;
    }


    /**
     *
     * @param array $data
     * @return Communicator
     */
    function setData(array $data)
    {
        $this->data = $data;
        return $this;
    }


    /**
     *
     * @param array $data
     * @return Communicator
     */
    function mergeData(array $data)
    {
        $data = array_merge($this->getData(), $data);
        $this->setData($data);
        return $this;
    }


    /**
     *
     * @param string $key
     * @param mixed $value
     * @return Communicator
     */
    function addData($key , $value)
    {
        $data = array();
        $data[$key] = $value;
        $this->mergeData($data);

        return $this;
    }


    /**
     *
     * @return string
     */
    function toJson()
    {
        return json_encode($this->toArray());
    }


    /**
     * @param string $key
     * @param mixed $value
     */
    function __set($key, $value)
    {
        $this->data[$key] = $value;
    }


    /**
     * @param string $key
     * @return mixed
     */
    function __get($key)
    {
        $r = null;

        if(isset($this->data[$key]))
        {
            $r = $this->data[$key];
        }

        return $r;
    }


    /**
     * @param string $key
     * @return bool
     */
    function __isset($key)
    {
        return isset($this->data[$key]);
    }


    /**
     * @param string $key
     */
    function __unset($key)
    {
        if(isset($this->data[$key]))
        {
            unset($this->data[$key]);
        }
    }


    /**
     *
     * @return array
     */
    function toArray()
    {
        return array(
            'success' => $this->isSuccess(),
            'message' => $this->getMessage(),
            'data' => $this->getData(),
            'errors' => $this->getErrors()
        );
    }


    function debug()
    {
        echo '<pre>';
        echo print_r($this->toArray(), 1);
        echo '</pre>';
    }
}
