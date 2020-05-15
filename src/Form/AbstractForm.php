<?php

namespace Com\Form;

use Com\Interfaces\LazyLoadInterface;
use Com\Interfaces\ContainerAwareInterface;
use Com\Traits\ContainerAwareTrait;

use Laminas\Form\Form;
use Laminas\EventManager\Event;
use Laminas\EventManager\EventManagerAwareInterface;
use Interop\Container\ContainerInterface;
use Laminas\EventManager\EventManagerAwareTrait;


abstract class AbstractForm extends Form implements LazyLoadInterface, EventManagerAwareInterface, ContainerAwareInterface
{
    use EventManagerAwareTrait, ContainerAwareTrait;
    
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