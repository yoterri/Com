<?php

namespace Com\Form;

use Com\LazyLoadInterface;
use Com\ContainerAwareInterface;

use Zend\Form\Form;
use Zend\EventManager\Event;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventManagerAwareInterface;
use Interop\Container\ContainerInterface;

abstract class AbstractForm extends Form implements LazyLoadInterface, EventManagerAwareInterface, ContainerAwareInterface
{
    
    /**
     * @var ContainerInterface
     */
    protected $container;
    
    /**
     * @var EventManagerInterface
     */
    protected $eventManager;
    
    
    /**
     * @param array $data
     * @param array $except
     * @return AbstractForm
     */
    function reset($data = null, array $except = array())
    {
        if(empty($data))
        {
            $filter = $this->getInputFilter();
            $data = $filter->getValues();
        }

        $newData = [];
        foreach($data as $key => $value)
        {
            if(in_array($key, $except))
            {
                continue;
            }

            $newData[$key] = '';
        }
        
        $this->setData($newData);

        #
        return $this;
    }


    /**
     * @param array $data
     * @return AbstractForm
     */
    function setData($data)
    {
        if(is_object($data) && method_exists($data, 'toArray'))
        {
            $data = $data->toArray();
        }
        
        parent::setData($data);

        return $this;
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
     * @param $eventManager EventManagerInterface
     */
    function setEventManager(EventManagerInterface $eventManager)
    {
        $eventManager->addIdentifiers(array(
            get_called_class()
        ));
    
        $this->eventManager = $eventManager;
        
        # $this->getEventManager()->trigger('sendTweet', null, array('content' => $content));
        return $this;
    }
    
    
    /**
     * @return EventManagerInterface
     */
    public function getEventManager()
    {
        if(null === $this->eventManager)
        {
            $this->setEventManager(new EventManager());
        }
        
        return $this->eventManager;
    }
    
    
    /**
     * @return AbstractForm
     */
    function build()
    {
        $fields = $this->getFields();
        
        #
        $eventParams = array('fields' => $fields);
        $event = new Event('pre.build', $this, $eventParams);
        $this->getEventManager()->triggerEvent($event);
        if($event->propagationIsStopped())
        {
            return $this;
        }

        #
        $fields = $event->getParam('fields');
        foreach($fields as $field)
        {
            $this->add($field);
        }

        #
        $event = new Event('post.build', $this);
        $this->getEventManager()->triggerEvent($event);
        
        #
        return $this;
    }


    /**
     * @param array $fields
     * @return AbstractForm
     */
    function removeFields(array $fields)
    {
        foreach($fields as $item)
        {
            if(is_string($item) && $this->has($item))
            {
                $this->remove($item);
            }
        }

        return $this;
    }
    
    
    abstract function getFields();
}