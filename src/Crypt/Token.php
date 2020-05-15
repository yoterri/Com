<?php

namespace Com\Crypt;

use Com;
use \Laminas\ServiceManager\ServiceLocatorInterface;
use Com\Crypt\Password;

class Token
{
    
    /**
     *
     * @var string
     */
    protected $publicKey = 'N86T7YOJLKJH';
    /**
     *
     * @var string
     */
    protected $privateKey = 'SDFUyrt@345&*yuio,!pkjhfyHASDOFIASHFISDSFAUSDSODUHIUSAH9876987';


    protected $serviceLocator;


    function __construct(ServiceLocatorInterface $serviceLocator = null)
    {
        $this->serviceLocator = $serviceLocator;
    }


    /**
     * @param string $public
     * @param string $private
     */
    function setKeys($public, $private)
    {
        $this->setPublicKey($public);
        $this->setPrivateKey($private);
        return $this;
    }


    /**
     * @param string $key
     */
    function setPrivateKey($key)
    {
        if(empty($key))
        {
            throw new \Exception('$Key can\'t be empty');
        }

        $this->privateKey = $key;
        return $this;
    }


    /**
     * @return string
     */
    function getPrivateKey()
    {
        return $this->privateKey;
    }


    /**
     * @param string $key
     */
    function setPublicKey($key)
    {
        if(empty($key))
        {
            throw new \Exception('$Key can\'t be empty');
        }

        $this->publicKey = $key;
        return $this;
    }


    /**
     * @return string
     */
    function getPublicKey()
    {
        return $this->publicKey;
    }


    /**
     *
     * @return array
     */
    function getToken()
    {
        $salt = null;

        if($this->serviceLocator)
        {
            if($this->serviceLocator->has('config'))
            {
                $config = $this->serviceLocator->get('config');   
                $salt = isset($config['application_salt']) ? $config['application_salt'] : null;
            }
        }
        
        
        $time = time();
        $publicKey = $this->getPublicKey();
        
        $plain = $publicKey . $time . $this->getPrivateKey() . $salt;
        
        $p = new Password();
        $code = $p->encode($plain);
        $r = [];

        $r['key']  = $publicKey;
        $r['code'] = $code;
        $r['time'] = $time;

        $r['token_key']  = $publicKey;
        $r['token_code'] = $code;
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
        
        $k = '<input type="hidden" name="token_key"  value="' . $token['token_key']  . '">' . PHP_EOL;
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
            if($this->serviceLocator)
            {
                if($this->serviceLocator->has('config'))
                {
                    $config = $this->serviceLocator->get('config');
                    $salt = isset($config['application_salt']) ? $config['application_salt'] : null;
                }
            }

            $plain = $publicKey . $tokenTime . $this->getPrivateKey() . $salt;
            
            $p = new Password();
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
        $tokenKey  = isset($_POST['token_key'])  ? $_POST['token_key']  : '';
        $tokenCode = isset($_POST['token_code']) ? $_POST['token_code'] : '';
        $tokenTime = isset($_POST['token_time']) ? $_POST['token_time'] : '';
        
        return $this->validate($tokenCode, $tokenKey, $tokenTime);
    }
}
