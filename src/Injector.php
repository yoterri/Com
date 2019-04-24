<?php

namespace Com;

use Interop\Container\ContainerInterface;

class Injector
{

    /**
     * @var array
     */
    protected $config = array();
    
    
    /**
     * @param array $config
     */
    function __construct(array $config = null)
    {
        if($config)
        {
            $this->setConfig($config);
        }
    }


    /**
     * @var array $config
     * @return Injector
     */
    function setConfig(array $config)
    {
        $this->config = $config;
        return $this;
    }


    /**
     * @return array
     */
    function getConfig()
    {
        return $this->config;
    }


    public function __invoke(ContainerInterface $container, $instance)
    {
        $config = $this->getConfig();
        foreach($config as $key => $conf)
        {
            if($instance instanceof $key)
            {
                if(!is_array($conf))
                {
                    $conf = array($conf);
                }

                foreach($conf as $injectorClass)
                {
                    $this->_inject($container, $instance, $injectorClass);
                }
            }
        }
    }


    protected function _inject(ContainerInterface $container, $instance, $injectorClass)
    {
        $injector = new $injectorClass();
        if(is_callable($injector))
        {
            $injector($container, $instance);
        }

        return $instance;
    }
}