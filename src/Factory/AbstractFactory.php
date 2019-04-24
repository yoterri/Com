<?php

namespace Com\Factory;

use Zend\ServiceManager\Factory\AbstractFactoryInterface;
use Interop\Container\ContainerInterface;
use Com\Injector;

class AbstractFactory implements AbstractFactoryInterface
{

    static $injector;
    
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        $flag = false;

        $implements = @class_implements($requestedName);
        if(is_array($implements))
        {
            $flag = in_array('Com\Interfaces\LazyLoadInterface', $implements);
        }

        return $flag;
    }


    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $injector = $this->_getInjector($container);

        $instance = (null === $options) ? new $requestedName : new $requestedName($options);
        $injector($container, $instance);

        return $instance;
    }


    protected function _getInjector(ContainerInterface $container)
    {
        if(!self::$injector)
        {
            $injectorConf = array();
            $config = $container->get('config');
            if(isset($config['interface_injector']) && is_array($config['interface_injector']))
            {
                $injectorConf = $config['interface_injector'];
            }

            self::$injector = new Injector($injectorConf);
        }

        return self::$injector;
    }
}