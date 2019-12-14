<?php

namespace Com\InputFilter;

use Com\Interfaces\ContainerAwareInterface;
use Com\Interfaces\LazyLoadInterface;
use Com\Traits\ContainerAwareTrait;

use Interop\Container\ContainerInterface;
use Zend\InputFilter\InputFilter;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\EventManager\Event;
use Zend\Validator\ValidatorChain;

abstract class AbstractInputFilter extends InputFilter implements LazyLoadInterface, EventManagerAwareInterface, ContainerAwareInterface
{
    use ContainerAwareTrait, EventManagerAwareTrait;


    /**
     * @var array
     */
    protected $params = array();


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


    function setData($data)
    {
        if(is_object($data) && method_exists($data, 'toArray'))
        {
            $data = $data->toArray();
        }

        #
        $eventParams = array('data' => $data, 'params' => $this->params);
        $event = $this->_triggerFilterEvent($eventParams, 'pre.set.data');
        $data = $event->getParam('data');

        #
        $this->_setDataPre($data);
        
        #
        parent::setData($data);

        #
        $eventParams = array('data' => $data, 'params' => $this->params);
        $event = $this->_triggerFilterEvent($eventParams, 'post.set.data');

        return $this;
    }


    protected function _setDataPre($data)
    {
        ;
    }


    function build()
    {
        $filters = $this->getFilters();

        $eventParams = array('filters' => $filters, 'params' => $this->params);
        $event = $this->_triggerFilterEvent($eventParams, 'pre.build');
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
        $event = $this->_triggerFilterEvent($eventParams, 'post.build');
        
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
     * @param array $fields
     */
    function removeValidators(array $fields)
    {
        foreach($fields as $item)
        {
            if(is_string($item) && $this->has($item))
            {
                $el = $this->get($item);
                $el->setValidatorChain(new ValidatorChain());
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
        $event = $this->_triggerFilterEvent($eventParams, 'pre.get.values');
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


    /**
     * Is the data set valid?
     *
     * @param  mixed|null $context
     * @throws Exception\RuntimeException
     * @return bool
     */
    public function isValid($context = null)
    {
        #
        $eventParams = array('content' => $context);
        $event = $this->_triggerFilterEvent($eventParams, 'pre.validate');
        $context = $event->getParam('context');

        #
        $flag = parent::isValid($context);

        $eventParams = array('content' => $context, 'flag' => $flag);
        $event = $this->_triggerFilterEvent($eventParams, 'post.validate');
        $flag = $event->getParam('flag');

        #
        return $flag;
    }


    protected function _triggerFilterEvent(array $eventParams, $eventName)
    {
        $event = new Event($eventName, $this, $eventParams);
        
        $this->getEventManager()->triggerEvent($event);
        
        return $event;
    }


    abstract function getFilters();

}
