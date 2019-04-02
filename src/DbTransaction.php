<?php

namespace Com;
use Interop\Container\ContainerInterface;
use Zend\Db\Adapter\AdapterAwareInterface;
use Zend\Db\Adapter\Adapter;

use Com\LazyLoadInterface;

class DbTransaction implements AdapterAwareInterface, LazyLoadInterface
{

    /**
     * @var Zend\Db\Adapter\Adapter
     */
    protected $adapter;


    /**
     * @param Adapter $adapter
     */
    function __construct(Adapter $adapter = null)
    {
        if($adapter)
        {
            $this->setDbAdapter($adapter);
        }
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
        $adapter = $this->getDbAdapter();
        if(!$adapter)
        {
            throw new \Exception('Cant\'t start transaction, adapter was not set.');
        }

        $connection = $adapter->getDriver()->getConnection();
        if(!$connection->inTransaction())
        {
            $connection->beginTransaction();
        }

        return $this;
    }

    
    function complete()
    {
        $adapter = $this->getDbAdapter();
        if(!$adapter)
        {
            throw new \Exception('Cant\'t complete transaction, adapter was not set.');
        }

        $connection = $adapter->getDriver()->getConnection();
        if($connection->inTransaction())
        {
            $connection->commit();
        }

        return $this;
    }


    function rollback()
    {
        $adapter = $this->getDbAdapter();
        if(!$adapter)
        {
            throw new \Exception('Cant\'t rollback transaction, adapter was not set.');
        }

        $connection = $adapter->getDriver()->getConnection();
        if($connection->inTransaction())
        {
            $connection->rollback();
        }

        return $this;
    }
}