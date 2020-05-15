<?php

namespace Com\Enum;

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


    /**
     * @param string $property - property name
     * @param string $key
     * @return array | string
     */
    function get($property, $key = null)
    {
        if( !isset($this->$property) )
        {
            $p = htmlentities($property);
            throw new \Exception("Property '$p' was not found.");
        }

        if( !is_array($this->$property) )
        {
            $p = htmlentities($property);
            throw new \Exception("Property '$p' is not  avalid array.");
        }

        return $this->_get($this->$property, $key);
    }


    /**
     * @param string $property - property name
     * @return bool
     */
    function propertyExists($property)
    {
        return isset($this->$property);
    }


    /**
     * @param string $property - property name
     * @param string $key
     * @return bool
     */
    function keyExists($property, $key)
    {
        return $this->propertyExists($property) && isset($this->$property[$key]);
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

