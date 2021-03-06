<?php

namespace Com\Db;

/**
 * Events: 
 * db.prefixing
 * pre.insert
 * post.insert
 * pre.update
 * post.update
 * pre.delete
 * post.delete
 */

use Zend;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\EventManager\EventManager;
use Zend\EventManager\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\Db\Adapter\AdapterAwareInterface;
use Zend\EventManager\EventManagerAwareInterface;
use Interop\Container\ContainerInterface;
use Zend\Db\Sql\Where;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;

use Com\Entity\Record;
use Com\ContainerAwareInterface;
use Com\LazyLoadInterface;


class AbstractDb extends TableGateway implements AdapterAwareInterface, EventManagerAwareInterface, ContainerAwareInterface, LazyLoadInterface
{
    
    /**
     * @var ContainerInterface
     */
    protected $container;


    /**
     * Name of the database table without prefix
     *
     * @var string
     */
    protected $tableName = '';

    /**
     *
     * @var Name of the database
     */
    protected $schemaName = '';

    /**
     * The database adpater used to connect to the database
     * @var string
     */
    protected $adapterKey;

    /**
     * Name of the class used as entity
     *
     * @var string
     */
    protected $entityClassName = 'Com\Entity\Record';

    /**
     *
     * @var Zend\Stdlib\Hydrator\HydratorInterface
     */
    protected $hydrator;

    /**
     *
     * @var Zend\Db\ResultSet\ResultSet
     */
    protected $resultSet = null;

    /**
     * @var EventManagerInterface
     */
    protected $eventManager;

    /**
     *
     * @var bool
     */
    protected $enableDebug = false;


    /**
     *
     * @param string $features
     * @param Zend\Db\ResultSet\ResultSetInterface $resultSetPrototype
     */
    function __construct($features = null, Zend\Db\ResultSet\ResultSetInterface $resultSetPrototype = null)
    {
        // process features
        if($features !== null)
        {
            if($features instanceof Zend\Db\TableGateway\Feature\AbstractFeature)
            {
                $features = array(
                    $features 
                );
            }
            
            if(is_array($features))
            {
                $this->featureSet = new Zend\Db\TableGateway\Feature\FeatureSet($features);
            }
            elseif($features instanceof Zend\Db\TableGateway\Feature\FeatureSet)
            {
                $this->featureSet = $features;
            }
            else
            {
                throw new \Exception('TableGateway expects $feature to be an instance of an AbstractFeature or a FeatureSet, or an array of AbstractFeatures');
            }
        }
        else
        {
            $this->featureSet = new Zend\Db\TableGateway\Feature\FeatureSet();
        }
        
        // result prototype
        $this->resultSetPrototype = ($resultSetPrototype) ?  : new Zend\Db\ResultSet\ResultSet();
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
     * @param bool $val
     * @return AbstractDb
     */
    function setEnableDebug($val)
    {
        $this->enableDebug = (bool)$val;
        return $this;
    }
    
    
    
    /**
     * @param $eventManager EventManagerInterface
     * @return AbstractDb
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
     * @return string
     */
    function getEntityClassName()
    {
        return $this->entityClassName;
    }


    protected function _build($className)
    {
        static $cache;
        $sm = $this->getContainer();

        if(empty($cache))
        {
            $cache = 'get';

            if(method_exists($sm, 'build'))
            {
                $cache = 'build';
            }
        }

        $result = call_user_func(array($sm, $cache), $className);
        return $result;
    }


    /**
     * @return Com\Entity\AsbtractEntity 
     */
    function getEntity()
    {
        $eClassName = $this->getEntityClassName();

        $entity = $this->_build($eClassName);
    
        $tmp = $entity->getDbClassName();
        if(empty($tmp))
        {
            $entity->setDbClassName(get_class($this));

            $pk = $entity->getPrimaryKeyColumn();
            if(empty($pk))
            {
                $this->setPrimaryKeyColumn(array('id'));
            }
        }

        return $entity;
    }



    /**
     *
     * @see Zend\Db\TableGateway\AbstractTableGateway::initialize()
     */
    function initialize()
    {
        if($this->isInitialized)
        {
            return;
        }

        $eventParams = [];
        $event = new Event('db.prefixing', $this, $eventParams);

        $this->getEventManager()->triggerEvent($event);
        $prefix = $event->getParam('prefix');
        if(! empty($this->schemaName))
        {
            $this->table = new Zend\Db\Sql\TableIdentifier("{$prefix}{$this->tableName}", $this->schemaName);
        }
        else
        {
            $this->table = "{$prefix}{$this->tableName}";
        }
        
        // Sql object (factory for select, insert, update, delete)
        $this->sql = new Zend\Db\Sql\Sql($this->getDbAdapter(), $this->table);
        
        $this->hydrator = new Zend\Hydrator\ObjectProperty();
        
        // check sql object bound to same table
        if($this->sql->getTable() != $this->table)
        {
            throw new \Exception('The table inside the provided Sql object must match the table of this TableGateway');
        }
        
        return parent::initialize();
    }


    /**
     *
     * @param array $data
     * @return Zend\Db\Adapter\Driver\ResultInterface
     */
    function doInsert(array $data)
    {
        $eventParams = ['data' => $data];
        $event = new Event('pre.insert', $this, $eventParams);
        
        $this->getEventManager()->triggerEvent($event);
        if(!$event->propagationIsStopped())
        {
            $data = $event->getParam('data');
            
            $sql = $this->getSql();
            $insert = $sql->insert();
            $insert->values($data);

            if($this->enableDebug)
            {
                $this->debugSql($insert, false);
            }
            
            $statement = $sql->prepareStatementForSqlObject($insert);
            
            $this->executeInsert($insert);
            $result = $this->getLastInsertValue();
            
            #
            $eventParams = [
                'data' => $data,
                'source' => $this,
                'result' => $result,
                'last_insert_value' => $result,
            ];
            $event = new Event('post.insert', $this, $eventParams);
            
            $this->getEventManager()->triggerEvent($event);
        }
        else
        {
            $result = null;
        }
        
        return $result;
    }


    /**
     *
     * @param array $data
     * @param string|array|closure $where
     * @return Zend\Db\Adapter\Driver\ResultInterface
     */
    function doUpdate(array $data, $where)
    {
        $eventParams = ['data' => $data, 'where' => $where];
        $event = new Event('pre.update', $this, $eventParams);
        
        $this->getEventManager()->triggerEvent($event);
        if(!$event->propagationIsStopped())
        {
            $data = $event->getParam('data');
            $where = $event->getParam('where');
        
            $sql = $this->getSql();
            $update = $sql->update();
            $where = $update->set($data)->where($where);

            if($this->enableDebug)
            {
                $this->debugSql($update, false);
            }
            
            $statement = $sql->prepareStatementForSqlObject($update);
            
            $tmp = $statement->execute();

            $result = $tmp->getAffectedRows();
            
            #
            $eventParams = [
                'data' => $data,
                'source' => $this,
                'where' => $where,
                'result' => $result,
                'affected_rows' => $result,
            ];
            $event = new Event('post.update', $this, $eventParams);
            
            $this->getEventManager()->triggerEvent($event);
        }
        else
        {
            $result = null;
        }
        
        return $result;
    }


    /**
     *
     * @param string|array|closure $where
     * @return \Zend\Db\Adapter\Driver\ResultInterface
     */
    function doDelete($where)
    {
        $eventParams = ['where' => $where];
        $event = new Event('pre.delete', $this, $eventParams);
        
        $this->getEventManager()->triggerEvent($event);
        if(!$event->propagationIsStopped())
        {
            $where = $event->getParam('where');
            
            $sql = $this->getSql();
            $delete = $sql->delete();
            $delete->where($where);

            if($this->enableDebug)
            {
                $this->debugSql($delete, false);
            }
            
            $statement = $sql->prepareStatementForSqlObject($delete);
            
            $tmp = $statement->execute();

            $result = $tmp->getAffectedRows();
            
            #
            $eventParams = [
                'where' => $where,
                'source' => $this,
                'result' => $result,
                'affected_rows' => $result,
            ];
            $event = new Event('post.delete', $this, $eventParams);
            
            $this->getEventManager()->triggerEvent($event);
        }
        else
        {
            $result = null;
        }
        
        return $result;
    }


    /**
     * Returns a ResultSet of Com\Entity\AbstractEntity
     *
     * @param Where|\Closure|string|array|Predicate\PredicateInterface $where
     * @param array $cols
     * @param string $order
     * @param int $count
     * @param int $offset
     * @param Com\Entity\AbstractEntity $entity
     * 
     * @return Zend\Db\ResultSet\AbstractResultSet
     * 
     * @throws \RuntimeException
     */
    function findBy($where = null, $cols = array(), $order = null, $count = null, $offset = null, Com\Entity\AbstractEntity $entity = null)
    {
        $sql = $this->getSql();
        $select = $sql->select();

        $select = $this->_apply($select, $where, $cols, $order, $count, $offset);
        
        if(empty($entity))
        {
            $enity = $this->getEntity();
        }
        
        return $this->executeCustomSelect($select, $entity);
    }


    /**
     *
     * @param int $primaryKey
     * @param string $colName
     * @param Com\Entity\AbstractEntity $entity
     * 
     * @return Zend\Db\ResultSet\AbstractResultSet
     */
    function findRecord($primaryKey = null, $colName = null, Com\Entity\AbstractEntity $entity = null)
    {
        $data = array();

        if($primaryKey)
        {
            $rowset = $this->findByPrimaryKey($primaryKey, $colName, $entity);
        }
        else
        {
            $rowset = $this->findAll(null, $entity);
        }

        return $rowset;
    }



    /**
     * @return Where
     */
    function getWhere()
    {
        return new Where();
    }



    /**
     * Returns a ResultSet of \Com\Entity\Record
     *
     * @param Zend\Db\Sql\Select
     * @param Where|\Closure|string|array|Predicate\PredicateInterface $where
     * @param array $cols
     * @param string $order
     * @param int $count
     * @param int $offset
     * @return Zend\Db\Sql\Select
     */
    protected function _apply($select, $where = null, $cols = null, $order = null, $count = null, $offset = null)
    {
        if(is_null($cols) || '' == $cols)
        {
            $cols = array();
        }

        if($where)
            $select->where($where);
        
        if($cols)
            $select->columns($cols);
        
        if($order)
            $select->order($order);
        
        if($offset)
            $select->offset($offset);
        
        if($count)
            $select->limit($count);

        return $select;
    }


    /**
     *
     * @param string $order
     * @param Com\Entity\AbstractEntity $entity
     * 
     * @return Zend\Db\ResultSet\AbstractResultSet
     */
    function findAll($order = null, Com\Entity\AbstractEntity $entity = null)
    {
        #new Zend\Db\ResultSet\ResultSet();
        $cols = array('*');
        
        return $this->findBy(null, $cols, $order, null, null, $entity);
    }


    /**
     *
     * @param int $mixed
     * @param string $colName
     * @param Com\Entity\AbstractEntity $entity
     * 
     * @return Com\Entity\AbstractEntity | null
     */
    function findByPrimaryKey($mixed, $colName = null, Com\Entity\AbstractEntity $entity = null)
    {
        if(! is_array($mixed))
        {
            if(empty($colName))
            {
                $colName = 'id';
            }
            
            $where = array();
            $where["$colName = ?"] = $mixed;
        }
        else
        {
            $where = $mixed;
        }
        
        return $this->findBy($where, array(), null, null, null, $entity)->current();
    }


    /**
     *
     * @param int $val
     * @param string $colName
     * @return bool
     */
    function existByPrimaryKey($val, $colName = null)
    {
        if(empty($colName))
        {
            $colName = 'id';
        }
        
        $where = new Where();
        $where->equalTo($colName, $val);
        
        return (1 == $this->count($where));
    }


    /**
     *
     * @param Where|\Closure|string|array|Predicate\PredicateInterface $where
     * @param string $group
     * @return int
     */
    function count($where = null, $group = null)
    {
        $sql = $this->getSql();
        $select = $sql->select();
        
        if($where)
        {
            $select->where($where);
        }

        if($group)
        {
            $select->group($group);
        }

        $cols = array();
        $cols['count'] = new Zend\Db\Sql\Predicate\Expression('COUNT(*)');
        $select->columns($cols);

        $entity = $this->_build('Com\Entity\Record');
        
        return $this->executeCustomSelect($select, $entity)->current()->count;
    }


    function truncate()
    {
        $tableName = $this->getTable();
        if(false === strpos($this->getTable(), '.'))
        {
            $driver = $this->getAdapter()->getPlatform();
            $tableName = $driver->quoteIdentifier($tableName);
        }

        $this->getAdapter()->query("TRUNCATE TABLE {$tableName};", Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
    }


    /**
     *
     * @param \Zend\Db\Sql\SqlInterface $sql Consulta sql a mostrar
     * @param string $exit Indica si se debe detener la ejecucion del código
     */
    function debugSql(\Zend\Db\Sql\SqlInterface $sql, $exit = true)
    {
        $str = $this->getSqlAsString($sql);
        
        echo '<pre>';
        echo $str;
        echo '</pre>';
        
        if($exit)
        {
            exit;
        }
    }


    /**
     * @param Zend\Db\Sql\AbstractPreparableSql $sql $sql
     * @return string
     */
    function getSqlAsString(Zend\Db\Sql\AbstractPreparableSql $sql)
    {
        $str = $sql->getSqlString($this->getDbAdapter()->getPlatform());
        
        return "$str";
    }


    /**
     *
     * @param \Zend\Db\Sql\Select $select
     * @param \Com\Entity\AbstractEntity $entity
     * 
     * @return Zend\Db\ResultSet\AbstractResultSet | mixed
     */
    function executeCustomSelect(Zend\Db\Sql\Select $select, Com\Entity\AbstractEntity $entity = null)
    {
        // prepare and execute

        if($this->enableDebug)
        {
            $this->debugSql($select, false);
        }

        $statement = $this->getSql()->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        
        // build result set
        $resultSet = clone $this->resultSetPrototype;
        
        if(is_null($entity))
        {
            $entity = $this->getEntity();
        }
        
        $resultSet->setArrayObjectPrototype($entity);
        $resultSet->initialize($result);
        
        return $resultSet;
    }


    /**
     *
     * @return string
     */
    function getAdpaterKey()
    {
        return $this->adapterKey;
    }


    function __toString()
    {
        $r = $this->tableName;
        
        if($this->schemaName)
        {
            $r = "{$this->schemaName}.$r";
        }
        else
        {
            $r = "$r";
        }
        
        return $r;
    }
}