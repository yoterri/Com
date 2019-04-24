<?php
/**
 * Abstract class used to represent a record from a database
 */
namespace Com\Entity;

use Zend;
use Com\Interfaces\LazyLoadInterface;
use Com\Db\AbstractDb;
use Com\Object\AbstractObject;

use Zend\Db\Sql\Where;
use Zend\Db\Sql\Expression;



abstract class AbstractEntity extends AbstractObject implements LazyLoadInterface
{

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
}
