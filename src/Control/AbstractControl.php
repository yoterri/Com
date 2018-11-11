<?php

namespace Com\Control;
use Interop\Container\ContainerInterface;
use Com\ContainerAwareInterface;
use Zend\InputFilter\InputFilter;
use Zend\Db\Adapter\AdapterAwareInterface;
use Zend\Db\Sql\Where;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\Db\Adapter\Adapter;
use Zend\EventManager\EventManager;
use Zend\EventManager\Event;
use Zend\EventManager\EventManagerInterface;
use Com\LazyLoadInterface;
use Com\InputFilter\AbstractInputFilter;
use Zend\Stdlib\Parameters;

abstract class AbstractControl implements 
    ContainerAwareInterface, AdapterAwareInterface, EventManagerAwareInterface, LazyLoadInterface
{

    /**
     *
     * @var Com\Communicator
     */
    protected $communicator;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var EventManagerInterface
     */
    protected $eventManager;

    /**
     * @var Zend\Db\Adapter\Adapter
     */
    protected $adapter;


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
     *
     * @param Adapter $adapter
     */
    function setDbAdapter(Adapter $adapter)
    {
        $this->adapter = $adapter;
        return $this;
    }


    /**
     *
     * @return \Zend\Db\Adapter\Adapter
     */
    function getDbAdapter()
    {
        return $this->adapter;
    }


    /**
     *
     * @return boolean
     */
    function isSuccess()
    {
        return $this->getCommunicator()->isSuccess();
    }


    /**
     *
     * @return \Com\Communicator
     */
    function getCommunicator()
    {
        if(! $this->communicator instanceof \Com\Communicator)
            $this->resetCommunicator();
        
        return $this->communicator;
    }


    /**
     *
     * @return \Com\Control\AbstractControl
     */
    function resetCommunicator()
    {
        $this->communicator = new \Com\Communicator();
        
        return $this;
    }


    /**
     * @param strign $message
     * @param array $data
     */
    function setSuccess($message = null, array $data = array())
    {
        $com = $this->getCommunicator();
        $com->setSuccess($message);
        $com->setData($data);

        return $this;
    }


    function setException(\Exception $e)
    {
        $a = defined('APP_ENV');
        $b = defined('APP_DEVELOPMENT');
        
        if(($a && $b) && (APP_ENV == APP_DEVELOPMENT))
        {
            $message = "<pre>$e</pre>";
        }
        else
        {
            $message = $e->getMessage();
        }
        
        $this->getCommunicator()->addError($message);
        
        return $this;
    }

    /**
     * @param AbstractInputFilter $filter
     * @return AbstractControl
     */
    function setFilterError(AbstractInputFilter $filter)
    {
        $messages = $filter->getMessages();
        $com = $this->getCommunicator();

        foreach($messages as $key => $item)
        {
            $message = current($item);
            $com->addError($message, $key);
        }

        return $this;
    }

    /**
     * @return Where
     */
    function getWhere()
    {
        return new Where();
    }


    /**
     * @param Parameters|array $params
     * @param array $fields
     * @param bool $removeZeros
     */
    function removeIfEmpty($params, array $fields, $removeZeros = false)
    {
        $isParam = ($params instanceof Parameters);
        foreach($fields as $field)
        {
            $isset = false;
            if($isParam)
            {
                if(isset($params->field))
                {
                    $value = $params->field;
                    if(('' === $value) || is_null($value) || ($removeZeros && 0 == $value))
                    {
                        unset($params->$field);
                    }
                }
            }
            else
            {
                if(isset($params[$field]))
                {
                    $value = $params[$field];
                    if(('' === $value) || is_null($value) || ($removeZeros && 0 == $value))
                    {
                        unset($params[$field]);
                    }
                }
            }
        }

        return $params;
    }

}