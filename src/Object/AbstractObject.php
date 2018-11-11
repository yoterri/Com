<?php
/**
 * Abstract class used to represent any single object having custom properties
 */
namespace Com\Object;

use Traversable;
use Zend;
use Interop\Container\ContainerInterface;
use Com\ContainerAwareInterface;
use Com\LazyLoadInterface;

abstract class AbstractObject implements ContainerAwareInterface, LazyLoadInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $_properties = array();

    /**
     * @var array
     */
    protected $properties = array();

    /**
     * @var array
     */
    protected $data = array();

    /**
     * @var array
     */
    protected $attr = array();

    /**
     * @var array
     */
    protected $rawData = array();

    /**
     *
     * @var Com\Communicator
     */
    protected $communicator;


    /**
     * Constructor
     *
     * @param array|Traversable|null $data
     */
    public function __construct($data = null)
    {
        if (null !== $data)
        {
            $this->populate($data);
        }
    }

    
    /**
     * @param string $key
     * @param mixed $value
     * @return AbstractObject
     */    
    final function setAttr($key, $value)
    {
        $this->attr[$key] = $value;
        return $this;
    }


    /**
     * @param string $key
     * @param mixed $dev
     * @return mixed
     */
    final function getAttr($key = null, $def = null)
    {
        if(empty($key))
        {
            return $this->attr;
        }

        if(isset($this->attr[$key]))
        {
            $def = $this->attr[$key];
        }
        
        return $def;
    }


    /**
     *
     * @return \Com\Communicator
     */
    function getCommunicator()
    {
        if(! $this->communicator instanceof \Com\Communicator)
            $this->resetCommunicator();
        
        return $this->communicator;
    }


    /**
     *
     * @return \Com\Control\AbstractControl
     */
    function resetCommunicator()
    {
        $this->communicator = new \Com\Communicator();
        
        return $this;
    }


    /**
     * @param array|Traversable|null $data
     * @return AbstractObject
     */
    function create($data = null)
    {
        $obj = new self($data);

        $sm = $this->getContainer();
        if($sm)
        {
            $obj->setContainer($sm);
        }

        return $obj;
    }
    
    
    /**
     * @param ContainerInterface $container
     */
    function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    
    /**
     * @return ContainerInterface
     */
    function getContainer()
    {
        return $this->container;
    }


    /**
     *
     * @param array|Traversable|AbstractOptions $data
     * @return \Com\Entity\AbstractEntity
     */
    function setFromArray($data)
    {
        $this->populate($data);
        return $this;
    }
    

    /**
     *
     * @param array|Traversable|AbstractOptions $data            
     * @return \Com\Entity\AbstractEntity
     */
    function populate($data)
    {
        if($data instanceof self)
        {
            $data = $data->toArray();
        }

        if($data instanceof AbstractObject)
        {
            $data = $data->toArray();
        }
        
        if (!is_array($data) && !$data instanceof Traversable)
        {
            throw new \Exception(sprintf('Parameter provided to %s must be an %s, %s or %s', __METHOD__, 'array', 'Traversable', 'Zend\Stdlib\AbstractOptions'));
        }

        $this->rawData = $data;
        
        foreach($data as $key => $value)
        {
            $this->__set($key, $value);
        }
        
        return $this;
    }


    /**
     * @param array|Traversable|AbstractOptions $data            
     * @return \Com\Entity\AbstractEntity
     */
    function exchange($data)
    {
        return $this->exchangeArray($data);
    }
    

    /**
     * @param array|Traversable|AbstractOptions $data            
     * @return \Com\Entity\AbstractEntity
     */
    function exchangeArray($data)
    {
        $this->_properties = array();
        
        $prop = $this->getProperties();
        foreach($prop as $key)
        {
            $this->__set($key, null);
        }

        $this->populate($data);

        $this->attr = array();
        $this->_onExchangeArray();

        return $this;
    }

    

    protected function _onExchangeArray()
    {
        ;
    }


    /**
     * @return array
     */
    function toArray()
    {
        return $this->extract();

        /*
        if(!$includeCustom)
        {
            return $this->extract();
        }
        
        $array = array();
        $methods = array();
        
        $keys = $this->getProperties();    

        foreach($keys as $key)
        {
            $getter = 'get' . str_replace(' ', '', str_replace('_', ' ', $key));
            
            $methods[$getter] = $key;
            $array[$key] = $this->__get($key);
        }
        
        $classMethods = get_class_methods($this);
        
        foreach($classMethods as $method)
        {
            if(preg_match('/^get/', $method))
            {
                $lower = strtolower($method);
                
                $flag = ('getarraycopy' == $lower);
                $flag = $flag || ('getproperties' == $lower);
                $flag = $flag || ('getcontainer' == $lower);

                if(!$flag && !isset($methods[$lower]))
                {
                    $method = substr($method, 3);
                    $separated = preg_replace('/(?<!\ )[A-Z]/', '_$0', $method);
                    if ('_' == substr($separated, 0, 1))
                    {
                        $separated = substr($separated, 1);
                    }

                    $prop = strtolower($separated);
                    $val = $this->__get($prop);
                    
                    #if (!is_null($val))
                    {
                        $array[$prop] = $this->__get($prop);
                    }
                }
            }
        }
        
        return $array;
        */
    }


    /**
     *
     * @return array
     */
    function extract()
    {
        $data = array();
        $keys = $this->getProperties();
        foreach($keys as $key)
        {
            $tmp = $this->__get($key);
            if(is_null($tmp) || (is_string($tmp) && '' == $tmp))
            {
                $tmp = $this->_getPropertyDefaultValue($key);
            }
            
            $data[$key] = $tmp;
        }
        
        return $data;
    }
    

    /**
     * @return array
     */
    function getArrayCopy()
    {
        return $this->toArray();
    }


    /**
     * @return string
     */
    function toJson()
    {
        return Zend\Json\Encoder::encode($this->toArray());
    }
    

    /**
     * @return string
     */
    function toString()
    {
        $r = '';
        try
        {
            $r .= '<pre>';
            $r .= get_class($this) . ' => ';
            $r .= print_r($this->toArray(), 1);
            $r .= '</pre>';
        }
        catch(\Exception $e)
        {
            ;
        }
       
        return $r;
    }


    function set($key, $value)
    {
        $this->__set($key, $value);
        return $this;
    }


    /**
     *
     * @param string $key            
     * @param mixed $value            
     */
    public function __set($key, $value)
    {
        $setter = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
        
        if(method_exists($this, $setter))
        {
            $this->{$setter}($value);
        }
        else
        {
            if($this->propertyExist($key))
            {
                $this->data[$key] = $value;
            }
        }

        $this->rawData[$key] = $value;
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
            if(!$this->propertyExist($key))
            {
                $className = get_called_class();
                throw new \RuntimeException("Undefined property {$className}.{$key}'");
            }

            $value = isset($this->data[$key]) ? $this->data[$key] : null;
        }
        
        return $value;
    }
    

    /**
     * Test if a configuration property exists
     *
     * @param string $key            
     * @return bool
     */
    public function __isset($key)
    {
        return $this->propertyExist($key);
    }
    

    /**
     * Remove a configuration property
     *
     * @param string $key            
     * @return void
     */
    public function __unset($key)
    {
        $index = array_search($key, $this->properties);
        if($index !== false)
        {
            unset($this->properties[$index]);

            if(isset($this->data[$key]))
            {
                unset($this->data[$key]);
            }
        }
    }


    /**
     * @param string $property            
     * @return boolean
     */
    function propertyExist($property)
    {
        $prop = $this->getProperties();
        return in_array($property, $prop);
    }
    

    /**
     *
     * @return array
     */
    function getProperties()
    {
        if(!$this->_properties)
        {
            foreach($this->properties as $key => $value)
            {
                if(!is_numeric($key))
                {
                    $value = $key;
                }

                $this->_properties[] = $value;
            }
        }

        return $this->_properties;
    }

    
    protected function _getPropertyDefaultValue($property)
    {
        $def = null;

        if($this->propertyExist($property))
        {
            if(isset($this->properties[$property]))
            {
                $def = $this->properties[$property];
            }
        }

        return $def;
    }


    function __toString()
    {
        return $this->toString();
    }


    /**
     * @param strign $fieldOrValue
     * @param strign $format
     * @return string
     */
    function getDateFormatted($fieldOrValue = null, $format = 'F d, Y', $def = '-')
    {
        if(!empty($fieldOrValue))
        {
            if($this->propertyExist($fieldOrValue))
            {
                $value = $this->$fieldOrValue;
            }
            else
            {
                $value = $fieldOrValue;
            }

            if(!empty($value))
            {
                $exploded = explode('-', $value);
                if(3 == count($exploded))
                {
                    $y = $exploded[0];
                    $m = $exploded[1];
                    $d = $exploded[2];

                    if(checkdate($m, $d, $d))
                    {
                        $t = strtotime($value);
                        $def = date($format, $t);
                    }
                }
            }
        }

        return $def;
    }


    /**
     * @param strign $fieldOrValue
     * @param strign $format
     * @return string
     */
    function getTimeFormatted($fieldOrValue = null, $format = 'H:i:s', $def = '-')
    {
        if(!empty($fieldOrValue))
        {
            if($this->propertyExist($fieldOrValue))
            {
                $value = $this->$fieldOrValue;
            }
            else
            {
                $value = $fieldOrValue;
            }

            if(!empty($value))
            {
                $def = date('H:i:s', $value);
            }
        }

        return $def;
    }


    /**
     * @param strign $fieldOrValue
     * @param int $decimals
     * @param string $decimalPoint
     * @param string $thousands
     * @param string $def
     * @return string
     */
    function getNumberFormatted($fieldOrValue = null, $decimals = 2, $decimalPoint = '.', $thousands = ',', $def = '0.00')
    {
        if(!empty($fieldOrValue))
        {
            $f = $this->propertyExist($fieldOrValue);
            if($f)
            {
                $value = $this->$fieldOrValue;
            }
            else
            {
                $value = $fieldOrValue;
            }

            if(!empty($value))
            {
                $def = number_format($value, $decimals, $decimalPoint, $thousands);
            }
        }

        return $def;
    }


    /**
     * Backward compatibility
     *
     * @param strign $fieldOrValue
     * @param int $decimals
     * @param string $decimalPoint
     * @param string $thousands
     * @param string $def
     * @return string
     */
    function getNumberFormat($fieldOrValue = null, $decimals = 2, $decimalPoint = '.', $thousands = ',', $def = '0.00')
    {
        return $this->getNumberFormatted($fieldOrValue, $decimals, $decimalPoint, $thousands, $def);
    }
}
