<?php
/**
 * Abstract class used to represent a record from a database
 */
namespace Com\Entity;

use Laminas;
use Com\Interfaces\LazyLoadInterface;
use Com\Db\AbstractDb;
use Com\Object\AbstractObject;

use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Where;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\AdapterAwareTrait;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Paginator\Adapter\DbSelect;
use Laminas\Paginator\Paginator;
use Laminas\Db\Adapter\AdapterAwareInterface;


abstract class AbstractEntity extends AbstractObject implements  AdapterAwareInterface, LazyLoadInterface
{
    use AdapterAwareTrait;

    /**
     * @example \User\Db\News
     * @var string
     */
    protected $dbClassName = null;


    /**
     * @var array 
     */
    protected $primaryKeyColumn = array();


    /**
     * @return Adapter
     */
    function getDbAdapter()
    {
        return $this->adapter;
    }


    /**
     * @param array $value
     * @return AbstractEntity
     */
    function setPrimaryKeyColumn(array $value)
    {
        $this->primaryKeyColumn = $value;
        return $this;
    }


    /**
     * @return array
     */
    function getPrimaryKeyColumn()
    {
        $pk = $this->primaryKeyColumn;
        if(!count($pk))
        {
            # if no primary key was set, we asume there should be a column named `id`
            $pk = array('id');
        }

        return $pk;
    }


    /**
     * @param string $value
     * @return AbstractEntity
     */
    function setDbClassName($value)
    {
        $this->dbClassName = $value;
        return $this;
    }


    /**
     * @return string
     */
    function getDbClassName()
    {
        return $this->dbClassName;
    }


    /**
     * @return Com\Db\AbstractDb
     */
    function getDbClass()
    {
        $db = null;

        $className = $this->getDbClassName();
        if(!empty($className))
        {
            $sm = $this->getContainer();
            $db = $sm->get($className);
        }

        return $db;
    }

 
    /**
     * @return array  
     */
    function getEntityColumns($columnPrefrix = null, $tableAlias = null, array $exclude = array())
    {
        $properties = $this->getProperties();

        if(count($exclude))
        {
            foreach($exclude as $key)
            {
                $index = array_search($key, $properties);
                if($index !== false)
                {
                    unset($properties[$index]);
                }
            }
        }

        #
        if(!empty($columnPrefrix) || !empty($tableAlias))
        {
            $newProperties = array();

            $counter = 0;
            foreach($properties as $value)
            {
                if(!empty($tableAlias))
                {
                    $newKey = "{$columnPrefrix}{$value}";
                    $newValue = new Expression("{$tableAlias}.{$value}");
                }
                else
                {
                    $newKey = $counter;
                    $newValue = "{$columnPrefrix}{$value}";
                }

                $newProperties[$newKey] = $newValue;
                $counter++;
            }

            $properties = $newProperties;
            unset($newProperties);
        }

        #
        return $properties;
    }


    /**
     * @return array
     */
    function toArrayUpdated()
    {
        $r = array();
        foreach($this->data as $key => $value)
        {
            if(isset($this->rawData[$key]))
            {
                $r[$key] = $this->__get($key);
            }
        }

        return $r;
    }
    

    /**
     * @param array $ref - the key are the fields used as foreign key. Values are used as primary keys in the reference table
     * @param AbstractDb $db
     * @param AbstractEntity $entity
     * @return 
     */
    function findRelated(array $ref, AbstractDb $db, AbstractEntity $entity = null)
    {
        $where = new Where();

        foreach($ref as $foreign => $key)
        {
            $val = $this->data[$foreign];

            $where->equalTo($key, $val);
        }

        return $db->findBy($where, array(), null, null, null, $entity);
    }



    function fillByPrimaryKey()
    {
        $db = $this->getDbClass();

        $where = $this->_buildWhereCondition($this->_getPkWhere());

        $row = $db->findBy($where)->current();
        
        if($row)
        {
            $this->exchange($row);
            return true;
        }
        else
        {
            return false;
        }
    }


    function fillBy($fieldOrWhere, $value = null)
    {
        $db = $this->getDbClass();

        if(!empty($value))
        {
            $where = new Where();
            $where->equalTo($fieldOrWhere, $value);

            $fieldOrWhere = $where ;
        }

        $rowset = $db->findBy($fieldOrWhere);
        
        if($rowset->count() > 1)
        {
            throw new \Exception('More than one record was found');
        }

        $row = $rowset->current();

        if($row)
        {
            $this->exchange($row);
            return true;
        }
        else
        {
            return false;
        }
    }


    /**
     * @return int | bool
     */
    function doInsert()
    {
        $in = $this->toArray();

        $db = $this->getDbClass();
        $result = $db->doInsert($in);

        if(1 == count($this->primaryKeyColumn))
        {
            $column = $this->primaryKeyColumn[0];
            if($result > 0)
            {
                $this->$column = $result;
            }
        }
        else
        {
            $result = true;
        }

        return $result;
    }


    /**
     * @return int
     */
    function doUpdate()
    {
        $where = $this->_buildWhereCondition($this->_getPkWhere());

        $in = $this->toArray();

        $db = $this->getDbClass();
        $result = $db->doUpdate($in, $where);

        return $result;
    }


    /**
     * @return int
     */
    function doDelete()
    {
        $where = $this->_buildWhereCondition($this->_getPkWhere());

        $db = $this->getDbClass();
        $result = $db->doDelete($where);

        return $result;
    }


    /**
     * @return array
     */
    protected function _getPkWhere()
    {
        $pk = $this->getPrimaryKeyColumn();
        $where = array();
        foreach($pk as $item)
        {
            $value = (string)trim($this->$item);
            if('0' === $value || !empty($value))
            {
                $where[$item] = $value;
            }
        }

        if(!count($where))
        {
            throw new \Exception('No primary key was provided.');
        }

        return $where;
    }


    /**
     * @return Where
     */
    protected function _buildWhereCondition($where)
    {
        # in this case we asume that the $where variable
        # contains the name of the column that is set as primary key
        if(is_string($where))
        {
            $col = $where;
            $val = $this->$col;

            $where = new Where();
            $where->equalTo($col, $val);
        }

        # in this case we assume that the user is providing a set of columns names
        # Also in this case we are going to use the AND operator
        elseif(is_array($where))
        {
            $tmp = $where;
            $where = new Where();

            foreach($tmp as $key => $value)
            {
                if(is_numeric($key))
                {
                    $col = $value;
                    $val = $this->$col;

                    $where->equalTo($col, $val);
                }
                else
                {
                    $where->equalTo($key, $value);
                }
            }
        }

        # if the user is not prividing a Where object
        # then we asume that the primary key name is `id`
        elseif(!$where instanceof Where)
        {
            $col = 'id';
            $val = $this->id;

            $where = new Where();
            $where->equalTo($col, $val);
        }

        return $where;
    }


    /**
     * @return Where
     */
    protected function _getWhere()
    {
        return new Where();
    }





    /**
     * @param  string|AbstractDb $dbRel   
     * @param  array|Where       $cond  - ['id_from_remote_table1' => 'id_local', 'id_from_remote_table2' => 'enabled'];
     * @return Laminas\Db\ResultSet\AbstractResultSet
     */
    function findRel($dbRel, $cond)
    {
        if (!$dbRel instanceof AbstractDb) {
            if (!is_string($dbRel)) {
                throw new \Exception('Invalid variable type $dbRel provided');
            }

            $dbRel = $this->getContainer()->get($dbRel);
        }

        if (is_array($cond)) {
            if (!count($cond)) {
                throw new \Exception('No condition provided');
            }

            $where = new Where();
            foreach ($cond as $remote => $localVal) {
                if (is_string($localVal) && in_array($localVal, $this->properties)) {
                    $localVal = $this->$localVal;
                }

                if (is_null($localVal)) {
                    $where->isNull($remote);
                } elseif (is_array($localVal)) {
                    $where->in($remote, $localVal);
                } else {
                    $where->equalTo($remote, $localVal);
                }
            }

            $cond = $where;
        }

        if (!$cond instanceof Where) {
            throw new \Exception('Invalid variable type $cond provided');
        }

        return $dbRel->findBy($cond);
    }


    /**
     * @param  string|AbstractDb $dbRel   
     * @param  array|Where       $cond  - ['id_from_remote_table1' => 'id_local', 'id_from_remote_table2' => 'enabled'];
     * @return Laminas\Db\ResultSet\AbstractResultSet
     */
    function countRel($dbRel, $cond)
    {
        if (!$dbRel instanceof AbstractDb) {
            if (!is_string($dbRel)) {
                throw new \Exception('Invalid variable type $dbRel provided');
            }

            $dbRel = $this->getContainer()->get($dbRel);
        }

        if (is_array($cond)) {
            if (!count($cond)) {
                throw new \Exception('No condition provided');
            }

            $where = new Where();
            foreach ($cond as $remote => $localVal) {
                if (is_string($localVal) && in_array($localVal, $this->properties)) {
                    $localVal = $this->$localVal;
                }

                if (is_null($localVal)) {
                    $where->isNull($remote);
                } elseif (is_array($localVal)) {
                    $where->in($remote, $localVal);
                } else {
                    $where->equalTo($remote, $localVal);
                }
            }

            $cond = $where;
        }

        if (!$cond instanceof Where) {
            throw new \Exception('Invalid variable type $cond provided');
        }

        return $dbRel->count($cond);
    }

    protected function _findRel($methodName, $args, $countArguments)
    {
        if (0 == $countArguments) {
            $cond = [];
        } else {
            $cond = $args[0];
        }

        if ($countArguments > 1) {
            $namespace = end($args);
        } else {
            $namespace = 'App\Db';
        }

        $className = substr($methodName, 7);
        $className = "{$namespace}\\{$className}";

        return $this->findRel($className, $cond);
    }


    protected function _countRel($methodName, $args, $countArguments)
    {
        if (0 == $countArguments) {
            $cond = [];
        } else {
            $cond = $args[0];
        }

        if ($countArguments > 1) {
            $namespace = end($args);
        } else {
            $namespace = 'App\Db';
        }

        $className = substr($methodName, 8);
        $className = "{$namespace}\\{$className}";

        return $this->countRel($className, $cond);
    }


    /**
     * @param  [type] $methodName [description]
     * @param  [type] $args       [description]
     * @return mixed
     */
    function __call($methodName, $args)
    {
        $methodNameLower = strtolower($methodName);
        $countArguments  = count($args);

        if ('findrel' == substr($methodNameLower, 0, 7)) {
            return $this->_findRel($methodName, $args, $countArguments);
        } elseif ('countrel' == substr($methodNameLower, 0, 8)) {
            return $this->_countRel($methodName, $args, $countArguments);
        }

        $className = get_class($this);
        throw new \Exception("Call to undefined method {$className}::{$methodName}");
    }


    /**
     * Returns a ResultSet of Com\Entity\AbstractEntity
     * @param string $relationName
     * @param Where|\Closure|string|array|Predicate\PredicateInterface $where
     * @param array $cols
     * @param string|array $group
     * @param string $order
     * @param int $count
     * @param int $offset
     * @param Com\Entity\AbstractEntity | string $entity
     * 
     * @return Laminas\Db\ResultSet\AbstractResultSet|Com\Entity\AbstractEntity
     * 
     * @throws \RuntimeException
     */
    function findRelation($relationName, Where $where = null, $cols = null, $group = null, $order = null, $count = null, $offset = null, $entity = null)
    {
        $localDb = $this->getDbClass();
        $config = $localDb->getRelationsConfig();
        if (!isset($config[$relationName])) {
            throw new \Exception("Relation with name '{$relationName}' was not found");
        }

        $config = $config[$relationName];
        $localDb->checkRelationConfig($relationName, $config);

        if (!$where) {
            $where = new Where();
        }

        $localColumnName = $config['local'];
        $relColumnName = $config['foreign'];
        $where->equalTo($relColumnName, $this->$localColumnName);

        #
        $relDb = $this->getContainer()->get($config['db']);
        if (!$entity) {
            $entity = $relDb->getEntity();
        }

        $querySelect = $relDb->buildQuerySelect('x', [], $where, $cols, $group, $order, $count, $offset);
        $rowset = $querySelect->getResult($entity);

        if ('one' == $config['type']) {
            return $rowset->current();
        } else {
            return $rowset;
        }
    }


    /**
     * Returns a ResultSet of Com\Entity\AbstractEntity
     * @param string $relationName
     * @param Where|\Closure|string|array|Predicate\PredicateInterface $where
     * @param string $group
     * @return int
     * @throws \RuntimeException
     */
    function countRelation($relationName, Where $where = null,  $group = null)
    {
        $localDb = $this->getDbClass();
        $config = $localDb->getRelationsConfig();
        if (!isset($config[$relationName])) {
            throw new \Exception("Relation with name '{$relationName}' was not found");
        }

        $config = $config[$relationName];
        $localDb->checkRelationConfig($relationName, $config);

        if (!$where) {
            $where = new Where();
        }

        $localColumnName = $config['local'];
        $relColumnName = $config['foreign'];
        $where->equalTo($relColumnName, $this->$localColumnName);

        $relDb = $this->getContainer()->get($config['db']);
        return $relDb->count($where, $group);
    }
}
