<?php

namespace Com\Form;

use Com\Communicator;
use Com\Control\AbstractControl;
use Zend\Form\Form;
use Com\LazyLoadInterface;
use Zend\EventManager\Event;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerInterface;
use Com\ContainerAwareInterface;
use Zend\EventManager\EventManagerAwareInterface;
use Interop\Container\ContainerInterface;

abstract class AbstractForm extends Form  implements LazyLoadInterface, yEventManagerAwareInterface, ContainerAwareInterface
{
    
    /**
     * @var ContainerInterface
     */
    protected $container;
    
    
    /**
     * @var EventManagerInterface
     */
    protected $eventManager;
    
    
    function reset($data, array $except = [])
    {
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
    }


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
    
    
    function setCommunicator(Communicator $com)
    {
        if(!$com->isSuccess())
        {
            $err = $com->getGlobalErrors();
            if($err)
            {
                $global = ['global' => []];
                foreach($err as $msg)
                {
                    $global['global'][] = $msg;
                }
                
                $this->setMessages($global);
            }
            else
            {
                $err = $com->getErrors();
                if($err)
                {
                    $this->setMessages($err);
                }
            }
        }
        
        return $this;
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
    
    
    
    protected function _triggerFieldsEvent(array $fields)
    {
        $eventParams = array('fields' => $fields);
        $event = new Event('form.fields', $this, $eventParams);
        
        $this->getEventManager()->triggerEvent($event);
        
        return $event;
    }   
    
    
    function build()
    {
        $fields = $this->getFields();
        
        #
        $event = $this->_triggerFieldsEvent($fields);
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
        
        return $this;
    }
    
    
    abstract function getFields();
}