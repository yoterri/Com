<?php
/**
 *
 * @author <yoterri@ideasti.com>
 * @copyright 2014
 *
 */
namespace Com;

class Random
{

    const TYPE_ALPHA = 1;

    const TYPE_ALNUM = 2;

    const TYPE_NUMERIC = 3;

    protected $_letters = 'abcdefghijklmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ';

    protected $_numbers = '1234567890';

    protected $_min = 6;

    protected $_max = 32;

    /**
     *
     * @var int
     */
    protected $_type = self::TYPE_NUMERIC; // 1=alpha|2=alnum|3=numeric
    
    /**
     *
     * @var string
     */
    protected $_data;

    protected $_seed;

    protected $_merge;

    /**
     * max :int [default:32]
     * min :int [default:6]
     * type :string (1=alpha|2=alnum|3=numeric)
     * seed :string
     *
     * @param array $options            
     */
    function __construct($options = null)
    {
        $this->setOptions($options);
    }

    /**
     * max :int [default:32]
     * min :int [default:6]
     * type :string (1=alpha|2=alnum [default]|3=numeric)
     * seed :string
     *
     * @param array $options            
     * @return \Com\Random
     */
    function setOptions($options)
    {
        \Com\Options::setOptions($this, $options);
        return $this;
    }

    /**
     *
     * @param string $seed            
     * @param bool $merge            
     * @return \Com\Random
     */
    function setSeed($seed, $merge = true)
    {
        if(! empty($seed))
        {
            $this->_seed = $seed;
            $this->_merge = $merge;
        }
        
        return $this;
    }

    protected function _mergeSeed()
    {
        if($this->_seed)
        {
            if($this->_merge)
            {
                $this->_data .= $this->_seed;
            }
            else
            {
                $this->_data = $this->_seed;
            }
        }
    }

    /**
     *
     * @param int $min            
     * @return \Com\Random
     */
    function setMin($min)
    {
        $this->_min = abs((int) $min);
        return $this;
    }

    /**
     *
     * @return int
     */
    function getMin()
    {
        return $this->_min;
    }

    /**
     *
     * @param int $max            
     * @return \Com\Random
     */
    function setMax($max)
    {
        $this->_max = abs((int) $max);
        return $this;
    }

    /**
     *
     * @return int
     */
    function getMax($max)
    {
        return $this->_max;
    }

    /**
     *
     * @param int $type
     *            1=alpha|2=alnum|3=numeric
     * @return \Com\Random
     */
    function setType($type)
    {
        $this->_type = $type;
        
        if(1 === $this->_type)
        {
            $this->_data = $this->_letters;
        }
        elseif(2 === $this->_type)
        {
            $this->_data = $this->_letters . $this->_numbers;
        }
        else
        {
            $this->_data = $this->_numbers;
        }
        
        return $this;
    }

    /**
     *
     * @return int
     */
    function getType()
    {
        return $this->_type;
    }

    /**
     *
     * @return string
     */
    function get()
    {
        $this->_mergeSeed();
        
        $max_chars = mt_rand($this->_min, $this->_max);
        return substr(str_shuffle($this->_data), 0, $max_chars);
    }

    function __toString()
    {
        return $this->get();
    }
}