<?php

namespace Com;
use Interop\Container\ContainerInterface;
use Com\ContainerAwareInterface;
use Zend\Db\Adapter\AdapterAwareInterface;
use Com\LazyLoadInterface;
use Zend\Db\Adapter\Adapter;

class Transaction implements LazyLoadInterface, ContainerAwareInterface, AdapterAwareInterface
{

    /**
     * @var ContainerInterface
     */
    protected $container;


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


    function start()
    {
        $connection = $this->getDbAdapter()->getDriver()->getConnection();
        if(!$connection->inTransaction())
        {
            $connection->beginTransaction();
        }

        return $this;
    }

    
    function complete()
    {
        $connection = $this->getDbAdapter()->getDriver()->getConnection();
        if($connection->inTransaction())
        {
            $connection->commit();
        }

        return $this;
    }


    function rollback()
    {
        $connection = $this->getDbAdapter()->getDriver()->getConnection();
        if($connection->inTransaction())
        {
            $connection->rollback();
        }

        return $this;
    }
}