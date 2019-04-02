<?php

namespace Com\Control;
use Interop\Container\ContainerInterface;
use Zend\InputFilter\InputFilter;
use Zend\Db\Adapter\AdapterAwareInterface;
use Zend\Db\Sql\Where;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\Db\Adapter\Adapter;
use Zend\EventManager\EventManager;
use Zend\EventManager\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\Stdlib\Parameters;

use Com\Communicator;
use Com\ContainerAwareInterface;
use Com\LazyLoadInterface;
use Com\InputFilter\AbstractInputFilter;

abstract class AbstractControl implements ContainerAwareInterface, AdapterAwareInterface, EventManagerAwareInterface, LazyLoadInterface
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
     * @param string $eventName
     * @param array $eventParams
     * @return Event
     */
    function triggerEvent($eventName, array $eventParams)
    {
        $event = new Event($eventName, $this, $eventParams);
        $this->getEventManager()->triggerEvent($event);
        return $event;
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
     * @return \Com\Communicator
     */
    function getCommunicator()
    {
        return new \Com\Communicator();
    }


    /**
     * @param AbstractInputFilter $filter
     * @param Communicator $com
     * @return AbstractControl
     */
    function setFilterError(AbstractInputFilter $filter, Communicator $com = null)
    {
        $messages = $filter->getMessages();

        if(!$com)
        {
            $com = $this->getCommunicator();
        }
        
        foreach($messages as $key => $item)
        {
            $message = current($item);
            $com->addError($message, $key);
        }

        return $com;
    }


    /**
     * @param array | object $data
     * @return Parameters
     */
    function toParams($data)
    {
        if(is_object($data))
        {
            if(method_exists($data, 'toArray'))
            {
                $data = $data->toArray();
            }
            elseif(method_exists($data, 'getArrayCopy'))
            {
                $data = $data->getArrayCopy();
            }
        }

        #
        if(is_array($data))
        {
            $params = new Parameters($data);
        }
        elseif($data instanceof Parameters)
        {
            $params = $data;
        }
        else
        {
            throw new \Exception('Invalid parameter provided');
        }
        
        return $params;
    }


    /**
     * @param Parameters $params
     * @param string $inputFilterClassName
     * @param string $dbClassName
     *
     * @return Com\Communicator
     */
    protected function _save(Parameters $params, $inputFilterClassName, $dbClassName)
    {
        $sm = $this->getContainer();
        $com = new Communicator();

        try
        {
            $inputFilter = $sm->get($inputFilterClassName);
            $inputFilter->build();

            $inputFilter->setData($params->toArray());

            if($inputFilter->isValid())
            {
                $db = $sm->get($dbClassName);

                $dbKeyEventName = strtolower(str_replace('\\', '.', $dbClassName));
                if($params->id)
                {
                    $id = $params->id;

                    #
                    $entity = $db->findByPrimaryKey($id);
                    if($entity)
                    {
                        $values = $inputFilter->getValues(true);
                        $entity->populate($values);

                        #
                        $eventParams = array(
                            'entity' => $entity,
                            'params' => $params,
                            'values' => $values,
                        );
                        $event = $this->_triggerEvent("pre.update.{$dbKeyEventName}", $eventParams);
                        if(!$event->propagationIsStopped())
                        {
                            $entity = $event->getParam('entity');

                            #
                            $in = $entity->toArray();

                            #
                            $db->doUpdate($in, array('id' => $id));

                            #
                            $com->setSuccess('Successfully updated.', array('entity' => $entity));
                            $eventParams = array(
                                'communicator' => $com,
                                'entity' => $entity,
                                'params' => $params,
                                'values' => $values,
                            );
                            $event = $this->_triggerEvent("post.update.{$dbKeyEventName}", $eventParams);
                        }
                        else
                        {
                            $com->addError('Update was cancelled.');
                        }
                    }
                    else
                    {
                        $com->addError('Record not found.');
                    }
                }
                else
                {
                    $entity = $db->getEntity();
                    $values = $inputFilter->getValues();

                    $entity->exchange($values);

                    #
                    $eventParams = array(
                        'entity' => $entity,
                        'params' => $params,
                        'values' => $values,
                    );
                    $event = $this->_triggerEvent("pre.insert.{$dbKeyEventName}", $eventParams);
                    $entity = $event->getParam('entity');

                    if(!$event->propagationIsStopped())
                    {
                        #
                        $in = $entity->toArray();

                        $id = $db->doInsert($in);

                        $entity->id = $id;

                        $com->setSuccess('Successfully added.', array('entity' => $entity));

                        #
                        $eventParams = array(
                            'communicator' => $com,
                            'entity' => $entity,
                            'params' => $params,
                            'values' => $values,
                        );
                        $event = $this->_triggerEvent("post.insert.{$dbKeyEventName}", $eventParams);
                    }
                    else
                    {
                        $com->addError('Adding record was cancelled.');
                    }
                }
            }
            else
            {
                $this->setFilterError($inputFilter, $com);
            }
        }
        catch(\Exception $ex)
        {
            $com->setException($ex);
        }

        return $com;
    }


    /**
     * @param int $id
     * @param string $dbClassName
     *
     * @return Com\Communicator
     */
    protected function _delete($id, $dbClassName)
    {
        $sm = $this->getContainer();
        $com = new Communicator();

        try
        {
            $dbKeyEventName = strtolower(str_replace('\\', '.', $dbClassName));

            #
            $db = $sm->get($dbClassName);
            $entity = $db->findByPrimaryKey($id);
            if($entity)
            {
                $defMessage = 'Successfully deleted.';

                #
                $eventParams = array(
                    'entity' => $entity,
                    'message' => $defMessage,
                );
                $event = $this->_triggerEvent("pre.delete.{$dbKeyEventName}", $eventParams);
                $message = $event->getParam('message');

                if(!$event->propagationIsStopped())
                {
                    if(empty($message))
                    {
                        $message = $defMessage;
                    }

                    $db->doDelete(array('id' => $entity->id));

                    $com->setSuccess($message, array('entity' => $entity));

                    #
                    $eventParams = array(
                        'entity' => $entity,
                        'communicator' => $com,
                    );
                    $event = $this->_triggerEvent("post.delete.{$dbKeyEventName}", $eventParams);
                }
                else
                {
                    if(empty($message))
                    {
                        $message = 'Deleting record was cancelled.';
                    }

                    $com->addError($message);
                }
            }
            else
            {
                $com->addError('Record not found.');
            }
        }
        catch(\Exception $ex)
        {
            $com->setException($ex);
        }

        return $com;
    }


    private function _triggerEvent($eventName, $eventParams)
    {
        $event = new Event($eventName, $this, $eventParams);
        $this->getEventManager()->triggerEvent($event);
        return $event;
    }
}