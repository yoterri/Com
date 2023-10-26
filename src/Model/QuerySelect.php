<?php

namespace Com\Model;

use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Where;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Paginator\Adapter\DbSelect;
use Laminas\Paginator\Paginator;

use Com\Interfaces\ContainerAwareInterface;
use Laminas\Db\Adapter\AdapterAwareInterface;
use Laminas\EventManager\EventManagerAwareInterface;
use Com\Interfaces\LazyLoadInterface;

use Laminas\Db\Adapter\AdapterAwareTrait;
use Laminas\EventManager\EventManagerAwareTrait;
use Com\Traits\ContainerAwareTrait;
use Com\Entity\Record;
use Com\Entity\AbstractEntity;
use Laminas\Db\ResultSet\ResultSetInterface;


class QuerySelect  implements ContainerAwareInterface, AdapterAwareInterface, EventManagerAwareInterface, LazyLoadInterface
{

    use AdapterAwareTrait, EventManagerAwareTrait, ContainerAwareTrait;
    

    /**
     * @var AbstractPreparableSql
     */
    protected $select;

    /**
     * ResultSet
     */
    protected $resultSet;



    function setResultSetPrototype(ResultSetInterface $resultSet)
    {
        $this->resultSet = $resultSet;
    }


    function getResultSetPrototype()
    {
        if (!$this->resultSet) {
            $resulset = new ResultSet();
            $this->setResultSetPrototype($resulset);
        }

        return $this->resultSet;
    }



    /**
     * @return QuerySelect
     */
    function prepare()
    {
        return $this->setSelect(new Select());
    }


    /**
     * @param Select $select
     * @return QuerySelect
     */
    function setSelect(Select $select)
    {
        $this->select = $select;
        return $this;
    }


    /**
     * @return Select
     */
    function getSelect()
    {
        if (!$this->select)  {
            $this->prepare();
        }

        return $this->select;
    }


    /**
     *
     * @return \Laminas\Db\Adapter\Adapter
     */
    function getDbAdapter()
    {
        return $this->adapter;
    }



    /**
     * @param AbstractEntity $entity
     * @param string|Laminas\Db\Adapter\Adapter $dbAdapter
     * @return ResultSet
     */
    function getResult(AbstractEntity $entity = null, $dbAdapter = null)
    {
        $dbAdapter = $this->_getAdapter($dbAdapter);
        $sql = new Sql($dbAdapter);

        $select = $this->getSelect();
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        
        // build result set
        $resultSet = $this->getResultSetPrototype();
        
        if (is_null($entity)) {
            $entity = new Record();
        }
        
        $resultSet->setArrayObjectPrototype($entity);
        $resultSet->initialize($result);
        #$resultSet->buffer();
        
        return $resultSet;
    }


    /**
     * @param int $pageNumber
     * @param int $itemsPerPage
     * @param string|Laminas\Db\Adapter\Adapter $dbAdapter
     * @return Paginator
     */
    function getPaginator($pageNumber = null, $itemsPerPage = null, $dbAdapter = null)
    {
        $dbAdapter = $this->_getAdapter($dbAdapter);
        $paginatorAdapter = new DbSelect($this->getSelect(), $dbAdapter, $this->getResultSetPrototype());
        $paginator = new Paginator($paginatorAdapter);

        if (!is_null($pageNumber)) {
            $paginator->setCurrentPageNumber($pageNumber);
        }

        if (!is_null($itemsPerPage)) {
            $paginator->setItemCountPerPage($itemsPerPage);
        }

        return $paginator;
    }


    public function __get($name)
    {
        switch (strtolower($name)) {
            case 'select':
                return $this->getSelect();
            default:
                throw new \Exception('Not a valid magic property for this object');
        }
    }



    /**
     *
     * @param string $exit Indica si se debe detener la ejecucion del código
     * @param string|Laminas\Db\Adapter\Adapter $dbAdapter
     */
    function debugSql($exit = true, $dbAdapter = null)
    {
        $str = $this->getSqlAsString($dbAdapter);
        
        echo '<pre>';
        echo $str;
        echo '</pre>';
        
        if ($exit) {
            exit;
        }
    }


    /**
     * @param string|Laminas\Db\Adapter\Adapter $dbAdapter
     * @return string
     */
    function getSqlAsString($dbAdapter = null)
    {
        $dbAdapter = $this->_getAdapter($dbAdapter);
        $sql = $this->getSelect();
        $str = $sql->getSqlString($dbAdapter->getPlatform());
        
        return "$str";
    }



    protected function _getAdapter($dbAdapter = null)
    {
        if (empty($dbAdapter)) {
            $dbAdapter = $this->getDbAdapter();
        } else {
            if (is_string($dbAdapter)) {
                $sm = $this->getContainer();
                $dbAdapter = $sm->get($dbAdapter);
            }
        }

        return $dbAdapter;
    }
}
}
