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

abstract class AbstractForm extends Form  implements LazyLoadInterface, EventManagerAwareInterface, ContainerAwareInterface
{
    
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var string
     */
    protected $version;
    
    
    /**
     * @var EventManagerInterface
     */
    protected $eventManager;


    /**
     * @param string $versionName
     */
    function setVersion($versionName)
    {
        $this->version = $versionName;
        return $this;
    }


    /**
     * @return string
     */
    function getVersion()
    {
        return $this->version;
    }
    
    
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


    protected function _triggerBuildEvent()
    {
        $event = new Event('form.built', $this);
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

        #
        $event = $this->_triggerBuildEvent();
        

        #
        $version = $this->getVersion();
        if($version)
        {
            $method = "{$version}Version";
            if(method_exists($this, $method))
            {
                $callback = array($this, $method);
                $result = call_user_func($callback);
                if(is_array($result))
                {
                    $this->removeFields($result);
                }
            }
        }

        return $this;
    }


    /**
     * @param array $fields
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