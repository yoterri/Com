<?php

namespace Com\InputFilter;
use Interop\Container\ContainerInterface;
use Com\ContainerAwareInterface;
use Zend\InputFilter\InputFilter;
use Com\LazyLoadInterface;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\Event;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerInterface;

abstract class AbstractInputFilter extends InputFilter implements LazyLoadInterface, EventManagerAwareInterface, ContainerAwareInterface
{

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $params = array();

    /**
     * @var EventManagerInterface
     */
    protected $eventManager;



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
     * @param array $data
     */
    function setParams($data)
    {
        foreach($data as $key => $value)
        {
            $this->setParam($key, $value);
        }

        return $this;
    }


    /**
     * @param string $key
     * @param string $value
     */
    function setParam($key, $value)
    {
        $this->params[$key] = $value;
        return $this;
    }


    /**
     * @param string $key
     * @param string $default
     */
    function getParam($key, $default = null)
    {
        if($this->hasParam($key))
        {
            $default = $this->params[$key];
        }

        return $default;
    }


    /**
     * @param string $key
     */
    function hasParam($key)
    {
        return isset($this->params[$key]);
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
     * Translate a message using the given text domain and locale
     *
     * @param string $message
     * @param string $textDomain
     * @param string $locale
     * @return string
     */
    function _($message, $textDomain = 'default', $locale = null)
    {
        $sm = $this->getContainer();

        if($sm->has('translator'))
        {
            $message = $sm->get('translator')->translate($message, $textDomain, $locale);
        }

        return $message;
    }


    function build()
    {
        $filters = $this->getFilters();

        $eventParams = array('filters' => $filters, 'params' => $this->params);
        $event = $this->_triggerFilterEvent($eventParams, 'input.filters');
        if($event->propagationIsStopped())
        {
            return $this;
        }

        #
        $filters = $event->getParam('filters');
        
        #
        if(is_array($filters))
        {
            foreach($filters as $filter)
            {
                $this->add($filter);
            }
        }

        #
        $eventParams = array('params' => $this->params);
        $event = $this->_triggerFilterEvent($eventParams, 'input.built');
        
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


    /**
     * Return a list of filtered values
     *
     * @return array
     */
    public function getValues($onlyProvided=false)
    {
        $values = parent::getValues();

        #
        $eventParams = array('values' => $values, 'only_provided' => $onlyProvided);
        $event = $this->_triggerFilterEvent($eventParams, 'input.values');
        if($event->propagationIsStopped())
        {
            return $values;
        }

        #
        $values = $event->getParam('values');
        $onlyProvided = $event->getParam('only_provided');

        #
        if(!$onlyProvided)
        {
            return $values;
        }

        #
        foreach($values as $key => $value)
        {
            if(!isset($this->data[$key]))
            {
                unset($values[$key]);
            }
        }

        return $values;
    }



    protected function _triggerFilterEvent(array $eventParams, $eventName)
    {
        $event = new Event($eventName, $this, $eventParams);
        
        $this->getEventManager()->triggerEvent($event);
        
        return $event;
    }


    abstract function getFilters();

}