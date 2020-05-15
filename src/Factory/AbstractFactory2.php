<?php

namespace Com\Factory;

use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Interop\Container\ContainerInterface;
use Laminas\EventManager\EventManager;
use Com\Db\AbstractDb;

class AbstractFactory2 implements AbstractFactoryInterface
{
    static protected $eventManager;

    #canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        $flag = false;

        if('\\' == substr($requestedName, 0, 1))
        {
            $requestedName = substr($requestedName, 1);
        }

        $sub = substr($requestedName, 0, 4);
        if(('Com\\' == $sub) || ('App\\' == $sub))
        {
            $flag = true;
        }

        if(!$flag)
        {
            $implements = class_implements($requestedName, true);
            if(is_array($implements))
            {
                $flag = in_array('Com\Interfaces\LazyLoadInterface', $implements);
            }
        }

        return $flag;
    }


    #createServiceWithName(ServiceLocatorInterface $container, $name, $requestedName)
    public function __invoke(ContainerInterface $container, $requestedName, array $options = NULL)
    {
        $instance = new $requestedName();

        $implements = class_implements($requestedName, true);
                
        if(in_array('Com\Interfaces\ContainerAwareInterface', $implements, true))
        {
            $instance->setContainer($container);
        }
        
        if(in_array('Laminas\Db\Adapter\AdapterAwareInterface', $implements, true))
        {
            $adapter = $container->get('adapter');
            
            if($instance instanceof AbstractDb)
            {
                $adapterKey = $instance->getAdpaterKey();
                if($adapterKey)
                {
                    $adapter = $container->get($adapterKey);
                }
            }
            
            $instance->setDbAdapter($adapter);
        }

        if(in_array('Laminas\EventManager\EventManagerAwareInterface', $implements, true))
        {
            if(in_array('Laminas\EventManager\EventManagerAwareInterface', $implements, true))
            {
                if($container->has('Laminas\EventManager\EventManager'))
                {
                    $eventManager = $container->has('Laminas\EventManager\EventManager');
                }
                else
                {
                    $eventManager = $this->getEventManager();
                }
                
                $instance->setEventManager($eventManager);
            }
        }
        
        if($instance instanceof AbstractDb)
        {
            $entityClassName = $instance->getEntityClassName();
            if($entityClassName)
            {
                $instance->getResultSetPrototype()->setArrayObjectPrototype($container->get($entityClassName));
            }
        
            $instance->initialize();
        }
        
        return $instance;
    }


    function getEventManager()
    {
        if(!self::$eventManager)
        {
            self::$eventManager = new EventManager();
        }

        return self::$eventManager;
    }
}