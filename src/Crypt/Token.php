<?php

namespace Com\Crypt;

use Com;
Use Zend;


class Token
{
    
    /**
     *
     * @var string
     */
    const PUBLIC_KEY = 'N86T7YOJLKJH';
    /**
     *
     * @var string
     */
    const PRIVATE_KEY = 'SDFUyrt@345&*yuio,!pkjhfyHASDOFIASHFISDSFAUSDSODUHIUSAH9876987';


    protected $serviceLocator;


    function __construct(\Zend\ServiceManager\ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }


    /**
     *
     * @return array
     */
    function getToken($publicKey = null)
    {
        $salt = null;
        if($this->serviceLocator->has('config'))
        {
            $config = $this->serviceLocator->get('config');   
            $salt = isset($config['application_salt']) ? $config['application_salt'] : null;
        }
        
        $time = time();
        if(empty($publicKey))
        {
            $publicKey = self::PUBLIC_KEY;
        }
        
        $plain = $publicKey . $time . self::PRIVATE_KEY . $salt;
        
        $p = new Com\Crypt\Password();
        
        $r = array();
        $r['token_key'] = $publicKey;
        $r['token_code'] = $p->encode($plain);
        $r['token_time'] = $time;
        
        return $r;
    }


    /**
     *
     * @return string
     */
    function getHiddenFields()
    {
        $token = $this->getToken();
        
        $k = '<input type="hidden" name="token_key" value="' . $token['token_key'] . '">' . PHP_EOL;
        $c = '<input type="hidden" name="token_code" value="' . $token['token_code'] . '">' . PHP_EOL;
        $t = '<input type="hidden" name="token_time" value="' . $token['token_time'] . '">' . PHP_EOL;
        
        return $k . $c . $t;
    }


    /**
     *
     * @param string $tokenCode
     * @param string $publicKey
     * @param int $tokenTime
     * @param int $ttl time to live in seconds
     * @return boolean
     */
    function validate($tokenCode, $publicKey, $tokenTime, $ttl = 1800)
    {
        $flag = false;
        
        // check token timeout
        $currentTime = time();
        if(($currentTime <= ($tokenTime + $ttl)) || (0 == $ttl))
        {
            $salt = null;
            if($this->serviceLocator->has('config'))
            {
                $config = $this->serviceLocator->get('config');
                $salt = isset($config['application_salt']) ? $config['application_salt'] : null;
            }

            $plain = $publicKey . $tokenTime . self::PRIVATE_KEY . $salt;
            
            $p = new Com\Crypt\Password();
            $flag = $p->validate($plain, $tokenCode);
        }
        
        return $flag;
    }


    /**
     *
     * @return bool
     */
    function validateFromPost()
    {
        $tokenKey = isset($_POST['token_key']) ? $_POST['token_key'] : '';
        $tokenCode = isset($_POST['token_code']) ? $_POST['token_code'] : '';
        $tokenTime = isset($_POST['token_time']) ? $_POST['token_time'] : '';
        
        return $this->validate($tokenCode, $tokenKey, $tokenTime);
    }
}
