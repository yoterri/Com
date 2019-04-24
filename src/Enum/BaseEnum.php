<?php

namespace Com\Enum;

use Zend;
use Com\Interfaces\LazyLoadInterface;


class BaseEnum implements LazyLoadInterface
{

    protected static $instance = null;

    const ENABLED = 'enabled';
    const DISABLED = 'disabled';


    protected $status = array(
    	self::ENABLED => 'Enabled',
        self::DISABLED => 'Disabled',
    );


    static function getInstance()
    {
        if(!self::$instance)
        {
            self::$instance = new static();
        }

        return self::$instance;
    }



	function getStatus($key = null)
    {
    	return $this->_get($this->status, $key);
    }


	protected function _get(array $values, $key = null)
    {
    	if(empty($key))
    	{
    		return $values;
    	}
    	else
    	{
    		if(!isset($values[$key]))
    		{
    			throw new \Exception("Key '$key' value not found");
    		}
    		else
    		{
    			return $values[$key];
    		}
    	}
    }

}

