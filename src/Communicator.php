<?php
namespace Com;
use Zend;

class Communicator
{

    /**
     *
     * @var bool
     */
    protected $_success = true;

    /**
     *
     * @var string
     */
    protected $_successMessage = '';

    /**
     *
     * @var array
     */
    protected $_data = array();

    /**
     *
     * @var array
     */
    protected $_errors = array();


    /**
     *
     * @return bool
     *
     */
    function isSuccess()
    {
        return $this->_success;
    }
    

    /**
     * @param string $message
     * @param array $data
     * @return \Com\Communicator
     */
    function setSuccess($message = null, array $data = null)
    {
        $this->clearErrors();

        $this->_successMessage = $message;
        $this->_success = true;

        if($data)
        {
            $this->setData($data);
        }

        return $this;
    }


    /**
     *
     * @return string
     */
    function getSuccess()
    {
        return $this->_successMessage;
    }


    /**
     *
     * @return string
     */
    function getSuccessMessage()
    {
        return $this->_successMessage;
    }


    /**
     *
     * @return \Com\Communicator
     */
    function setNoSuccess()
    {
        $this->_successMessage = '';
        $this->_success = false;
        return $this;
    }


    /**
     * @param \Exception $e
     * return \Com\Communicator 
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
     * @return \Com\Communicator
     */
    function addError($message, $key = null)
    {
        $this->setNoSuccess();
        $message = (string)$message;

        if(! empty($key))
        {
            if(! isset($this->_errors[$key]))
            {
                $this->_errors[$key] = array();
            }

            $this->_errors[$key][] = $message;
        }
        else
        {
            array_push($this->_errors, $message);
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
        return $this->_errors;
    }


    /**
     *
     * @return mixed
     */    
    function getError($key)
    {
        return $this->_errors[$key];
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
            if(is_numeric($key) && ! is_array($item))
            {
                $r[] = $item;
            }
        }

        return $r;
    }


    function getFieldErrors($fieldName = null)
    {
        $r = array();
        $errors = $this->getErrors();

        if(! empty($fieldName))
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
     * @return \Com\Communicator
     */
    function clearErrors()
    {
        $this->_errors = array();
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
            if(isset($this->_data[$key]))
            {
                $r = $this->_data[$key];
            }
            
            return $r;
        }
        else
        {
            return $this->_data;
        }
    }


    /**
     *
     * @return \Com\Communicator
     */
    function clearData()
    {
        $this->_data = array();
        return $this;
    }


    /**
     *
     * @param array $data
     * @return \Com\Communicator
     */
    function setData(array $data)
    {
        $this->_data = $data;
        return $this;
    }


    /**
     *
     * @param array $data
     * @return \Com\Communicator
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
     * @return \Com\Communicator
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
        return Zend\Json\Encoder::encode($this->toArray());
    }


    /**
     * @param string $key
     * @param mixed $value
     */
    function __set($key, $value)
    {
        $this->_data[$key] = $value;
    }


    /**
     * @param string $key
     * @return mixed
     */
    function __get($key)
    {
        $r = null;
        if(isset($this->_data[$key]))
        {
            $r = $this->_data[$key];
        }

        return $r;
    }


    /**
     * @param string $key
     * @return bool
     */
    function __isset($key)
    {
        return isset($this->_data[$key]);
    }


    /**
     * @param string $key
     */
    function __unset($key)
    {
        if(isset($this->_data[$key]))
        {
            unset($this->_data[$key]);
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
            'message' => $this->getSuccessMessage(),
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
