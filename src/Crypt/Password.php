<?php
/**
 * @author <yoterri@ideasti.com>
 * @copyright 2014
 */
namespace Com\Crypt;

class Password
{


    /**
     *
     * @var int
     */
    protected $_saltLength = 20;

    /**
     *
     * @var string
     */
    protected $_algorithm = 'sha1';

    /**
     *
     * @var bool
     */
    protected $_seeded = false;

    /**
     *
     * @var \Com\Crypt\Password
     */
    protected static $_instance;

    /**
     *
     * @param
     *            array
     */
    function __construct($options = array())
    {
        $this->setOptions($options);
    }

    /**
     *
     * @param
     *            array
     * @return \Com\Crypt\Password
     */
    function setOptions($options)
    {
        \Com\Options::setOptions($this, $options);
        return $this;
    }

    /**
     *
     * @param array $options            
     * @return \Com\Crypt\Password
     */
    static function getInstance($options = array())
    {
        $obj = \Com\Singleton::getObject(__CLASS__);
        
        if ($options)
            $obj->setOptions($options);
        
        return $obj;
    }

    /**
     *
     * @param string $algorithm            
     * @return \Com\Crypt\Password
     */
    function setAlgorithm($algorithm)
    {
        if ($algorithm != 'md5' && $algorithm != 'sha1')
            throw new \Exception('Just allow md5 or sha1 algorithm');
        
        $this->_algorithm = $algorithm;
        return $this;
    }

    /**
     *
     * @param int $saltLength            
     * @return \Com\Crypt\Password
     */
    function setSaltLength($saltLength)
    {
        $this->_saltLength = (int)$saltLength;

        if($this->_saltLength < 2)
        {
            $this->_saltLength = 2;
        }

        return $this;
    }


    /**
     *
     * @return int
     */
    function getSaltLength()
    {
        return $this->_saltLength;
    }


    /**
     *
     * @return string
     */
    function getAlgorithm()
    {
        return $this->_algorithm;
    }

    /**
     *
     * @param string $plain            
     * @return string
     */
    function encode($plain)
    {
        $password = '';
        
        for ($i = 0; $i < 10; $i ++)
            $password .= $this->_rand();
        
        $fn = $this->getAlgorithm();
        $salt = substr($fn($password), 0, $this->getSaltLength());
        
        $password = $fn($salt . $plain) . ':' . $salt;
        
        return $password;
    }

    /**
     *
     * @param string $plain            
     * @param string $encrypted            
     * @return bool
     */
    function validate($plain, $encrypted)
    {
        if (! empty($plain) && ! empty($encrypted))
        {
            $stack = explode(':', $encrypted);
            
            if (sizeof($stack) != 2)
                return false;
            
            $fn = $this->getAlgorithm();
            if ($fn($stack[1] . $plain) == $stack[0])
                return true;
        }
        
        return false;
    }

    /**
     *
     * @param int $min            
     * @param int $max
     *            return string
     */
    protected function _rand($min = null, $max = null)
    {
        if (! $this->_seeded)
        {
            mt_srand((double) microtime() * 1000000);
            $this->_seeded = true;
        }
        
        if (isset($min) && isset($max))
        {
            if ($min >= $max)
            {
                return $min;
            }
            else
            {
                return mt_rand($min, $max);
            }
        }
        else
        {
            return mt_rand();
        }
    }
}