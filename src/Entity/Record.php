<?php
/**
 * Represents a generic record from the database
 */
namespace Com\Entity;

use Com\Entity\AbstractEntity;
use Com\Interfaces\LazyLoadInterface;

class Record extends AbstractEntity implements LazyLoadInterface
{

    /**
     *
     * @param string $key            
     * @param mixed $value            
     */
    public function __set($key, $value)
    {
        $getter = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));

        if(!in_array($key, $this->properties))
        {
            $this->properties[] = $key;
        }        
        
        if (method_exists($this, $getter))
        {
            $this->{$getter}($value);
        }
        else
        {
            $this->data[$key] = $value;
        }
    }


    /**
     *
     * @param string $key            
     * @return mixed
     */
    function __get($key)
    {
        $value = null;
        
        $getter = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
        if (method_exists($this, $getter))
        {
            $value = $this->{$getter}();
        }
        else
        {
            if(isset($this->data[$key]))
                $value = $this->data[$key];
        }
        
        return $value;
    }
}
