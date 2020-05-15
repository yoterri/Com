<?php

namespace Com\Injector;

use Interop\Container\ContainerInterface;
use Laminas\EventManager\EventManager as theEventManager;
use Laminas\EventManager\EventManagerAwareInterface;

class EventManager 
{
    static protected $eventManager;


    public function __invoke(ContainerInterface $container, $instance)
    {
        if($instance instanceof EventManagerAwareInterface)
        {
            if($container->has('Laminas\EventManager\EventManager'))
            {
                $eventManager = $container->get('Laminas\EventManager\EventManager');
            }
            else
            {
                $eventManager = $this->getEventManager();
            }
            
            $instance->setEventManager($eventManager);
        }
    }


    function getEventManager()
    {
        if(!self::$eventManager)
        {
            self::$eventManager = new theEventManager();
        }

        return self::$eventManager;
    }
}