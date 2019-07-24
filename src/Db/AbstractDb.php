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
use Zend\EventManager\Event;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\Db\Sql\Where;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Db\Sql\Select;
use Zend\Paginator\Paginator;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\Db\Adapter\AdapterAwareTrait;
use Zend\Db\Adapter\AdapterAwareInterface;
use Zend\Db\Sql\AbstractPreparableSql;

use Com\Entity\Record;
use Com\Entity\AbstractEntity;
use Com\Interfaces\ContainerAwareInterface;
use Com\Interfaces\LazyLoadInterface;
use Com\Db\AbstractDbInterface;
use Com\Traits\ContainerAwareTrait;

class AbstractDb extends TableGateway implements AdapterAwareInterface, AbstractDbInterface, EventManagerAwareInterface, ContainerAwareInterface, LazyLoadInterface
{
    
    use AdapterAwareTrait, EventManagerAwareTrait, ContainerAwareTrait;
    

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
     *
     * @var bool
     */
    protected $enableDebug = false;

    /**
     * @var AbstractPreparableSql
     */
    protected $query;


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
     * @param string $type - select|insert|update|delete
     * @return AbstractDb
     */
    function prepareQuery($type = 'select')
    {
        $supported = ['select', 'insert', 'update', 'delete'];

        if(!in_array($type, $supported))
        {
            throw new \Exception("Unsupported query type '$type'");
        }


        $sql = $this->query = $this->getSql();
        $query = $sql->$type();
        return $this->setQuery($query);
    }


    /**
     * @param AbstractPreparableSql $query
     * @return AbstractDb
     */
    function setQuery(AbstractPreparableSql $query)
    {
        $this->query = $query;
        return $this;
    }


    /**
     * @return AbstractPreparableSql
     */
    function getQuery()
    {
        if(!$this->query)
            $this->prepareQuery();

        return $this->query;
    }


    /**
     * @param AbstractEntity | string $entity - only used when query is of type Select
     * @return mixed
     */
    function getResult($entity = null)
    {
        if($this->query instanceof Select)
        {
            return $this->executeCustomSelect($this->query, $entity);
        }
        elseif($this->query instanceof Insert)
        {

            $rawState = $this->query->getRawState();
            $columns = $rawState['columns'];
            $values = $rawState['values'];

            $data = array_combine($columns, $values);
            return $this->doInsert($data);
        }
        elseif($this->query instanceof Update)
        {

            $rawState = $this->query->getRawState();
            $data = $rawState['set'];
            $where = $rawState['where'];

            return $this->doUpdate($data, $where);
        }
        elseif($this->query instanceof Delete)
        {
            $rawState = $this->query->getRawState();
            $where = $rawState['where'];

            return $this->doDelete($where);
        }
    }


    /**
     * @param int $pageNumber
     * @param int $itemsPerPage
     * @param string|Zend\Db\Adapter\Adapter $adapter
     * @return Zend\Paginator\Paginator
     */
    function getPaginator($pageNumber = null, $itemsPerPage = null, $adapter = null)
    {
        if(!empty($adapter))
        {
            $sm = $this->getContainer();

            if(is_string($adapter))
            {
                $dbAdapter = $sm->get($adapter);
            }
            else
            {
                $dbAdapter = $dbAdapter;
            }
        }
        else
        {
            $dbAdapter = $this->getDbAdapter();
        }

        $paginatorAdapter = new DbSelect($this->getQuery(), $dbAdapter);
        $paginator = new Paginator($paginatorAdapter);

        if(!is_null($pageNumber))
        {
            $paginator->setCurrentPageNumber($pageNumber);
        }

        if(!is_null($itemsPerPage))
        {
            $paginator->setItemCountPerPage($itemsPerPage);
        }

        return $paginator;
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
     * @param Com\Entity\AbstractEntity | string $entity
     * 
     * @return Zend\Db\ResultSet\AbstractResultSet
     * 
     * @throws \RuntimeException
     */
    function findBy($where = null, $cols = array(), $order = null, $count = null, $offset = null, $entity = null)
    {
        $sql = $this->getSql();
        $select = $sql->select();

        $select = $this->_apply($select, $where, $cols, $order, $count, $offset);
        return $this->executeCustomSelect($select, $entity);
    }


    /**
     *
     * @param int $primaryKey
     * @param string $colName
     * @param Com\Entity\AbstractEntity | string $entity
     * 
     * @return Zend\Db\ResultSet\AbstractResultSet
     */
    function findRecord($primaryKey = null, $colName = null, $entity = null)
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
     * @param Com\Entity\AbstractEntity | string $entity
     * 
     * @return Zend\Db\ResultSet\AbstractResultSet
     */
    function findAll($order = null, $entity = null)
    {
        #new Zend\Db\ResultSet\ResultSet();
        $cols = array('*');
        
        return $this->findBy(null, $cols, $order, null, null, $entity);
    }


    /**
     * findByPrimaryKey(2);
     * findByPrimaryKey(2, 'column_name');
     * findByPrimaryKey(['col_name1 = ?' => 1, 'col_name2 = ?' => 2]);
     * 
     * @param mixed $mixed
     * @param string $colName
     * @param Com\Entity\AbstractEntity | string $entity
     * 
     * @return Com\Entity\AbstractEntity | null
     */
    function findByPrimaryKey($mixed, $colName = null, $entity = null)
    {

        if(!$entity)
        {
            $entity = $this->getEntity();
        }
        else
        {
            if(is_string($entity))
            {
                $entity = $this->getContainer()->get($entity);
            }
        }

        if(! is_array($mixed))
        {
            if(empty($colName))
            {
                $colName = $entity->getPrimaryKeyColumn();
            }

            if(is_array($colName)) 
            {
                $colName = current($colName);
            }

            $where = new Where();
            $where->equalTo($colName, $mixed);
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
            $entity = $this->getEntity();
            $colName = $entity->getPrimaryKeyColumn();
        }

        if(is_array($colName)) 
        {
            $colName = current($colName);
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
     * @param \Com\Entity\AbstractEntity | string $entity
     * 
     * @return Zend\Db\ResultSet\AbstractResultSet | mixed
     */
    function executeCustomSelect(Zend\Db\Sql\Select $select, $entity = null)
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

        if(!$entity)
        {
            $entity = $this->getEntity();
        }
        else
        {
            if(is_string($entity))
            {
                $entity = $this->getContainer()->get($entity);
            }
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
