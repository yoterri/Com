<?php
namespace Com\Control;
use Com\LazyLoadInterface;
use Zend\Db\ResultSet\AbstractResultSet;
use Com\Db\AbstractDb;
use Zend\Db\Sql\Literal;
use Zend\Db\Sql\Select;
use Com\ArrayUtils;
use Com\Entity\AbstractEntity;
use Zend\Stdlib\Parameters;

class Category extends AbstractControl implements LazyLoadInterface
{

    /**
     * @var AbstractDb
     */
    protected $dbCategory;

    /**
     * @var AbstractDb
     */
    protected $dbGroup;

    /**
     * @var AbstractDb
     */
    protected $dbClosure;

    /**
     * @var int
     */
    protected $groupId = 0;


    /**
     * @var string
     */
    protected $dbCategoryPrimary = 'id';

    /**
     * @var string
     */
    protected $dbCategoryLabel = 'name';





    /**
     * Fetch children.
     * 
     * Example to generate nested tree:
     * 
     *   $data = $this->getChildren(1, true, true);
     *   print_r($data);
     * 
     * @param  int $nodeId - node id
     * @param  boolean $self -  include self
     * @param  boolean $nested - nestify the result
     * @return array
     */
    public function getChildren($nodeId = 1, $self = true, $nested = true)
    {
        # If depth specified then self will be ignore.
        # @param  mixed $depth - node depth (e.g direct children = 1) 
        $depth = null;
        $useDepth = (!is_null($depth) && is_numeric($depth));

        #
        $this->_defaultGroup();

        #
        /*
        $lbl = $this->_getLabelColumn(); 
        $alias = $lbl['alias'];
        $column = $lbl['column'];
        */

        #
        $dbClosure = $this->getDbClosure();
        $dbCategory = $this->getDbCategory();

        #
        $cols = $dbCategory->getEntity()->getEntityColumns();
        foreach($cols as $key => $col)
        {
            $cols[$col] = new Literal("t.$col");
            unset($cols[$key]);
        }

        $cols += [
            'parent' => new Literal('c2.parent_id')
            ,'path' => new Literal('GROUP_CONCAT(bc.parent_id ORDER BY bc.depth DESC)')
            ,'depth' => new Literal('c1.depth')
            #,$alias => new Literal("t.{$column}")
        ];

        #echo '<pre>';
        #print_r($cols);
        #exit;

        #
        $select = new Select();
        $select->columns($cols);

        $select->from(['c1' => $dbClosure]);
        $select->join(['t' => $dbCategory], "t.{$this->dbCategoryPrimary} = c1.child_id", []);
        $select->join(['c2' => $dbClosure], new Literal('c2.depth IN(1) AND c2.child_id = c1.child_id'), [], 'left'); // ugh backticking INTs in #joins @TODO
        $select->join(['bc' => $dbClosure], '(c1.child_id = bc.child_id)', []);

        #
        $where = $this->getWhere()
            ->equalTo('c1.parent_id', $nodeId)
            ->equalTo('c1.group_id', $this->groupId);
    
        if(!$self)
        {
            $where->notEqualTo('c1.child_id', $nodeId);
        }

        if($useDepth)
        {
            $where->equalTo('c1.depth', $depth);
        }

        $select->where($where);

        #
        $select->group('c1.child_id');
        $select->group('c2.parent_id');
        $select->group('c1.depth');

        #$dbClosure->debugSql($select);

        #
        $result = [];

        #
        $rowset = $dbClosure->executeCustomSelect($select, $this->getContainer()->get('Com\Entity\Record'));

        #echo '<pre>';
        #print_r($rowset->toArray());
        #echo '</pre>';
        #exit;

        if($rowset->count())
        {
            if($nested && !$useDepth)
            {
                $trees = array();
                $root = null;
                $id = $this->dbCategoryPrimary;

                foreach($rowset as $row)
                {
                    $row = $row->toArray();
                    $trees[$row[$id]] = $row;
                }

                foreach($trees as $key => $row)
                {
                    if(!$root)
                    {
                        $root = $row['parent'];
                    }

                    $trees[$row['parent']]['children'][$key] =& $trees[$key];
                }

                $result = $trees[$root];

                if(!$self)
                {
                    $result = $result['children'];
                }
                else
                {
                    $result = isset($result['id']) ? $result : array_shift($result['children']);
                }
            }
            else
            {
                $result = $rowset->toArray();
            }
        }

        return $result;
    }


    /**
     * @param int|int[] $rootNode
     * @param string $sep
     * @return array
     */
    public function getRecords($rootNode = null, $sep = '-')
    {
        $this->_defaultGroup();

        if(is_null($rootNode))
        {
            $rowset = $this->getRoot();
            if($rowset)
            {
                $rootNode = $this->_array_pluck($rowset, 'id');
            }
            else
            {
                $rootNode = array(0);
            }
        }
        else
        {
            if(!is_array($rootNode))
            {
                $rootNode = array($rootNode);
            }
        }

        #
        $dbClosure = $this->getDbClosure();
        $dbCategory = $this->getDbCategory();

        $lbl = $this->_getLabelColumn(); 
        $alias = $lbl['alias'];
        $column = $lbl['column'];

        
        #
        $cols = $dbCategory->getEntity()->getEntityColumns();
        foreach($cols as $key => $col)
        {
            $cols[$col] = new Literal("rt2.$col");
            unset($cols[$key]);
        }

        $cols += [
            'parent' => new Literal('c3.parent_id')
            ,'path' => new Literal('GROUP_CONCAT(bc.parent_id ORDER BY bc.depth DESC)')
            ,'depth' => new Literal('c1.depth')
            ,$alias => new Literal("CONCAT(REPEAT('{$sep}', (c1.`depth`)), '', rt2.`{$column}`)")
        ];
        #
        

        $select = new Select();
        $select->columns($cols);

        $select->from(['rt1' => $dbCategory]);
        $select->join(['c1' => $dbClosure], "(c1.parent_id = rt1.{$this->dbCategoryPrimary})", []);
        $select->join(['rt2' => $dbCategory], 'c1.child_id = rt2.id', []);
        $select->join(['c2' => $dbClosure], new Literal('c2.child_id = rt2.id AND c2.depth = 0'), [], 'left');
        $select->join(['c3' => $dbClosure], new LIteral('c3.child_id = rt2.id AND c3.depth IN(1)'), [], 'left');
        $select->join(['bc' => $dbClosure], 'c1.child_id = bc.child_id', []);

        #
        $where = $this->getWhere()
            ->equalTo('c2.group_id', $this->groupId)
            ->in('rt1.id', $rootNode);

        $select->where($where);

        $select->group('c1.child_id');
        $select->group('c3.parent_id');
        $select->group('c1.depth');
        
        $select->order('path');

        #$dbClosure->debugSql($select);

        $rowset = $dbClosure->executeCustomSelect($select, $this->getContainer()->get('Com\Entity\Record'));
        if(!$rowset->count())
        {
            $result = array();
        }
        else
        {
            $result = $rowset->toArray();
        }

        return $result;
    }



    /**
     * @param int $nodeId
     * @return array
     */
    public function getRecord($nodeId)
    {
        $record = array();
        $records = $this->getRecords($nodeId);
        foreach($records as $row)
        {
            if($row['id'] == $nodeId)
            {
                $record = $row;
            }
        }

        return $record;
    }



    /**
     * Get parent(s) of current node.
     * 
     * @param int $nodeId - current node id
     * @param mixed $level - level up (e.g direct parent = 1)
     * @return array
     */
    /*
    public function getParent($nodeId, $level = NULL)
    {
        $ret = array();
        $this->_defaultGroup();

        $dbGroup = $this->getDbGroup();
        $dbClosure = $this->getDbClosure();

        $select = new Select();

        $select->from(['t' => $dbGroup]);
        $select->join(['c' => $dbClosure], 't.id = c.parent_id', []);

        $select->order('t.id');

        $where = $this->getWhere()
            ->equalTo('c.child_id', $nodeId)
            ->notEqualTo('c.parent_id', $nodeId)
            ->equalTo('c.group_id', $this->groupId);

        if(!is_null($level))
        {
            $where->equalTo('c.depth', $level);
        }

        $select->where($where);

        $dbClosure->debugSql($select);

        $rowset = $dbClosure->executeCustomSelect($select);
        if($rowset->count())
        {
            if($level)
            {
                $ret = $rowset->current()->toArray();
            }
            else
            {
                $ret = $rowset->toArray();
            }
        }

        return $ret;
    }
    */


    /**
     * Check if current node has children.
     * 
     * @param int $nodeId - node id
     * @return boolean
     */
    public function hasChildren($nodeId)
    {
        $this->_defaultGroup();

        $has = false;

        $where = $this->getWhere()
            ->equalTo('parent_id', $nodeId)
            ->equalTo('group_id', $this->groupId);

        $dbClosure = $this->getDbClosure();
        $childs = $dbClosure->findBy($where)->toArray();

        #
        $arr = ArrayUtils::pluck($childs, 'child_id');
        if($arr)
        {
            $where = $this->getWhere()
                ->in('parent_id', $arr)
                ->notEqualTo('child_id', $nodeId)
                ->equalTo('group_id', $this->groupId);

            $has = ($dbClosure->count($where) > 0);
        }
        
        return $has;
    }



    function getNested($self = true)
    {
        $roots = $this->getRoot() ;
        $nested = true;

        $result = array();
        foreach($roots as $item)
        {
            $nodeId = $item['id'];    
            $result[] = $this->getChildren($nodeId, $self, $nested);
        }

        return $result;
    }


    /**
     * Get (all) root nodes.
     */
    public function getRoot()
    {
        $this->_defaultGroup();

        $dbClosure = $this->getDbClosure();
        $dbCategory = $this->getDbCategory();


        $cols = $dbCategory->getEntity()->getEntityColumns();
        foreach($cols as $key => $col)
        {
            $cols[$col] = new Literal("t.$col");
            unset($cols[$key]);
        }

        $cols += [
            'parent' => new Literal('c2.parent_id')
            ,'depth' => new Literal('c1.depth')
        ];

        #
        $select = new Select();
        $select->columns($cols);

        $select->from(['c1' => $dbClosure]);
        $select->join(['t' => $dbCategory], "t.{$this->dbCategoryPrimary} = c1.child_id", []);
        $select->join(['c2' => $dbClosure], new Literal('c1.child_id = c2.child_id AND c2.parent_id <> c2.child_id'), [], 'left');

        $where = $this->getWhere()
            ->isNull('c2.child_id')
            ->equalTo('c1.group_id', $this->groupId);

        $select->where($where);

        #$dbClosure->debugSql($select);

        $rowset = $dbClosure->executeCustomSelect($select, $this->getContainer()->get('Com\Entity\Record'));

        $result = array();
        if($rowset)
        {
            foreach($rowset as $row)
            {
                $result[$row->id] = $row->toArray();
            }
        }

        return $result;
    }


    /**
     * Move node with its children to another node.
     * 
     * @link  http://www.mysqlperformanceblog.com/2011/02/14/moving-subtrees-in-closure-table/
     * 
     * @param int nodeId  node to be moved
     * @param int target node
     * @return bool
     */
    public function move($nodeId, $targetId)
    {
        $dbClosure = $this->getDbCategory();

        $adapter = $dbClosure->getDbAdapter();
        $connection1 = $adapter->getDriver()
            ->getConnection();


        try
        {
            $this->_move($nodeId, $targetId, $adapter);

            if($connection1->inTransaction())
            {
                $connection1->commit();
            }
        }
        catch(\Exception $e)
        {
            $this->getCommunicator()->setException($e);

            if($connection1->inTransaction())
            {
                $connection1->rollback();
            }
        }

        $this->getCommunicator()->isSuccess();
    }


    protected function _move($nodeId, $targetId, $adapter)
    {
        $this->_defaultGroup();

        $nodeId = intval($nodeId);
        $targetId = intval($targetId);

        $dbClosure = $this->getDbClosure();

        // MySQL’s multi-table DELETE
        $query1 = '';
        $query1 .= "DELETE a FROM $dbClosure AS a ";
        $query1 .= "JOIN $dbClosure AS d ON a.child_id = d.child_id ";
        $query1 .= "LEFT JOIN $dbClosure AS x ";
        $query1 .= 'ON x.parent_id = d.parent_id AND x.child_id = a.parent_id ';
        $query1 .= "WHERE (d.parent_id = {$nodeId}) ";
        $query1 .= "AND (x.parent_id IS NULL) ";
        $query1 .= "AND (a.group_id = $this->groupId) ";

        #
        $res1 = $adapter->query($query1)->execute();

        #
        $query2 = '';
        $query2 .= "INSERT INTO $dbClosure (`parent_id`, `child_id`, `depth`, `group_id`) ";
        $query2 .= "SELECT a.parent_id, b.child_id, (a.depth + b.depth + 1) AS depth, {$this->groupId} AS group_id ";
        $query2 .= "FROM $dbClosure AS a ";
        $query2 .= "JOIN $dbClosure AS b ";
        $query2 .= "WHERE b.parent_id = {$nodeId} ";
        $query2 .= "AND a.child_id = {$targetId} ";
        $query2 .= "AND a.group_id = {$this->groupId} ";

        $res2 = $adapter->query($query2)->execute();

        #echo $query1;
        #echo PHP_EOL, PHP_EOL;
        #echo $query2;


        #
        return $res1->getAffectedRows() && $res2->getAffectedRows();
    }



    /**
     * TODO: optional recursion
     * 
     * Delete node.
     * 
     * @param int $nodeId - node id
     * @param boolean $deleteReference - if true, it will also delete from category table
     * @return mixed
     */
    public function delete($nodeId, $deleteReference = true)
    {
        $this->_defaultGroup();

        $nodeId = intval($nodeId);

        $dbClosure = $this->getDbClosure();

        $dbAdapter = $dbClosure->getDbAdapter();

        $operand = "SELECT child_id AS id FROM $dbClosure WHERE parent_id = $nodeId AND group_id = $this->groupId";
        $query = "SELECT id, child_id FROM $dbClosure WHERE child_id IN ($operand) AND group_id = $this->groupId";

        $result = $dbAdapter->query($query)->execute();
        if($result->count() > 0)
        {
            $rowset = array();
            foreach($result as $row)
            {
                $rowset[] = $row;
            }

            #
            $childs = $this->_array_pluck($rowset, 'id');

            $where = $this->getWhere()
                ->in('id', $childs);

            $dbClosure->doDelete($where);
            
            if($deleteReference)
            {
                $childs = $this->_array_pluck($rowset, 'child_id');

                $where = $this->getWhere()
                    ->in('id', $childs);

                $this->getDbCategory()
                    ->doDelete($where);
            }
        }

        return true;
    }

    
    /**
     * @param $entity AbstractEntity
     * @param int $targetId target id
     */
    function edit(AbstractEntity $entity, $targetId = null)
    {
        $dbCategory = $this->getDbCategory();

        $connection1 = $dbCategory->getDbAdapter()
            ->getDriver()
            ->getConnection();


        try
        {
            if(!$connection1->inTransaction())
            {
                $connection1->beginTransaction();
            }

            $this->_checkEntity($entity, $dbCategory);

            if($entity->id)
            {
                throw new \Exception('No id value was provided');
            }

            $where = $this->getWhere()
                ->equalTo('id', $entity->id);

            $dbCategory->doUpdate($entity->toArray(), $where);


            if(!is_null($targetId))
            {
                $this->move($entity->id, $targetId);
            }


            if($connection1->inTransaction())
            {
                $connection1->commit();
            }
        }
        catch(\Exception $e)
        {
            $this->getCommunicator()->setException($e);

            if($connection1->inTransaction())
            {
                $connection1->rollback();
            }
        }

        $this->getCommunicator()->isSuccess();
    }


    /**
     * Add a node (as last child) of $targetId
     *
     * @param AbstractEntity $entity
     * @param int $targetId target id
     * @return int - the node id
     */
    public function add(AbstractEntity $entity, $targetId = 0)
    {
        $dbCategory = $this->getDbCategory();
        $connection1 = $dbCategory->getDbAdapter()
            ->getDriver()
            ->getConnection();

        #
        $dbClosure = $this->getDbClosure();
        $connection2 = $dbClosure->getDbAdapter()
            ->getDriver()
            ->getConnection();


        try
        {
            if(!$connection1->inTransaction())
            {
                $connection1->beginTransaction();
            }

            #
            if(!$connection2->inTransaction())
            {
                $connection2->beginTransaction();
            }

            #
            $this->_defaultGroup();

            $this->_checkEntity($entity, $dbCategory);

            $in = $entity->toArray();

            $dbCategory->doInsert($in);
            $nodeId = $dbCategory->getLastInsertValue();

            $id = $this->_add($nodeId, $targetId, $dbClosure->getDbAdapter());

            #
            $this->getCommunicator->setSuccess(null, array('id' => $id));


            if($connection1->inTransaction())
            {
                $connection1->commit();
            }
            
            if($connection2->inTransaction())
            {
                $connection2->commit();
            }

            return $id;
        }    
        catch(\Exception $e)
        {
            $this->getCommunicator()->setException($e);

            if($connection1->inTransaction())
            {
                $connection1->rollback();
            }
            
            if($connection2->inTransaction())
            {
                $connection2->rollback();
            }
        }

        $this->getCommunicator()->isSuccess();
    }



    /**
     * @param array $data
     */
    /*
    function updateTree(array $data)
    {
        $dbCategory = $this->getDbCategory();
        $connection1 = $dbCategory->getDbAdapter()
            ->getDriver()
            ->getConnection();        

        #
        $dbClosure = $this->getDbClosure();
        $connection2 = $dbClosure->getDbAdapter()
            ->getDriver()
            ->getConnection();


        try
        {
            if(!$connection1->inTransaction())
            {
                $connection1->beginTransaction();
            }

            #
            if(!$connection2->inTransaction())
            {
                $connection2->beginTransaction();
            }

            $entity = $dbCategory->getEntity();

            #
            $this->_updateTree($data, $entity);

            #
            $where = $this->getWhere()
                ->equalTo('group_id', $this->groupId);

            $dbClosure->doDelete($where);

            $this->_rebuildClosure($data, $dbClosure);

            #
            if($connection1->inTransaction())
            {
                $connection1->commit();
            }
            
            if($connection2->inTransaction())
            {
                $connection2->commit();
            }
        }
        catch(\Exception $e)
        {
            $this->getCommunicator()->setException($e);

            if($connection1->inTransaction())
            {
                $connection1->rollback();
            }
            
            if($connection2->inTransaction())
            {
                $connection2->rollback();
            }
        }

        $this->getCommunicator()->isSuccess();
    }
    */


    /**
     * This method was created to be called from updateTree()
     * @param array $data
     */
    /*
    protected function _updateTree(array &$data, AbstractEntity $entity)
    {
        #
        foreach($data as $key => $item)
        {
            $this->_saveItemTree($item, $entity);

            if(isset($item['children']) && is_array($item['children']))
            {
                $this->_updateTree($item['children'], $entity);
            }
        }
    }
    */


    /**
     * This method was created to be called from updateTree()
     * @param array $data
     * @param AbstractDb $dbClosure
     */
    /*
    protected function _rebuildClosure(array $data, $dbClosure, $parent = 0)
    {
        foreach($data as $item)
        {
            $this->_add($item['id'], $parent, $dbClosure->getDbAdapter());

            if(isset($item['children']) && is_array($item['children']))
            {
                $this->_rebuildClosure($item['children'], $dbClosure, $item['id']);
            }
        }
    }
    */










    /**
     * This method was created to be called from updateTree()
     * @param array $item
     * @param AbstractEntity $entity
     */
    /*
    protected function _saveItemTree(array &$item, AbstractEntity $entity)
    {
        $entity->id = $item['id'];
        if($entity->id > 0)
        {
            if($entity->fillByPrimaryKey())
            {
                $entity->populate($item);
                $entity->doUpdate();
            }
            else
            {
                throw new \Exception('Error Processing Request');
            }
        }
        else
        {
            $entity->id = null;
            $entity->populate($item);
            $item['id'] = $entity->doInsert();
        }
    }
    */


    


    /**
     * Add a node (as last child) of $targetId
     *
     * @param int $nodeId
     * @param int $targetId target id
     * @return int - the node id
     */
    protected function _add($nodeId, $targetId, $adapter)
    {
        $dbClosure = $this->getDbClosure();

        #
        $targetId = intval($targetId);
        $nodeId = intval($nodeId);

        $sql = 'SELECT parent_id, ' . $nodeId . ', (depth + 1), '. $this->groupId . '
                FROM ' . $dbClosure . ' 
                WHERE child_id = ' . $targetId . ' AND group_id = ' . $this->groupId . '
                UNION 
                SELECT ' . $nodeId . ', ' . $nodeId . ', 0, ' . $this->groupId;

        $query = 'INSERT INTO ' . $dbClosure . ' (`parent_id`, `child_id`, `depth`, `group_id`) ' . $sql;
        $result = $adapter->query($query)->execute();

        return $nodeId;
    }





    /**
     * @return array [description]
     */
    protected function _getLabelColumn()
    {
        $refColumn = $this->dbCategoryLabel;

        if(is_array($refColumn))
        {
            $alias = key($refColumn);
            $column = current($refColumn);
        }
        else
        {
            $alias = 'indented';
            $column = $refColumn;
        }

        return array(
            'column' => $column,
            'alias' => $alias,
        );
    }


    /**
     * @param AbstractDb $db
     * @return Category
     */
    function setDbClosure(AbstractDb $db)
    {
        $this->dbClosure = $db;

        return $this;
    }

    
    /**
     * @return AbstractDb
     */
    function getDbClosure()
    {
        if(!$this->dbClosure instanceof AbstractDb)
        {
            $this->dbClosure = $this->getContainer()
                ->get('Com\Db\Closure\Closure');
        }

        return $this->dbClosure;
    }


    /**
     * @param AbstractDb $db
     * @return Category
     */
    function setDbCategory(AbstractDb $db, $primaryKey = 'id', $labelColumn = 'name')
    {
        $this->dbCategory = $db;
        $this->dbCategoryPrimary = $primaryKey;
        $this->dbCategoryLabel = $labelColumn;
        return $this;
    }

    /**
     * @return AbstractDb
     */
    function getDbCategory()
    {
        if(!$this->dbCategory instanceof AbstractDb)
        {
            $dbCategory = $this->getContainer()
                ->get('Com\Db\Closure\Category');

            $this->setDbCategory($dbCategory);
        }

        return $this->dbCategory;
    }


    /**
     * @param AbstractDb $db
     * @return Category
     */
    function setDbGroup(AbstractDb $db)
    {
        $this->dbGroup = $db;

        return $this;
    }

    
    /**
     * @return AbstractDb
     */
    function getDbGroup()
    {
        if(!$this->dbGroup instanceof AbstractDb)
        {
            $dbGroup = $this->getContainer()
                ->get('Com\Db\Closure\Group');

            $this->setDbGroup($dbGroup);
        }

        return $this->dbGroup;
    }


    /**
     * @param string $name
     */
    function setGroup($name)
    {
        $this->_setGroup($name);
        return $this;
    }


    /**
     * 
     * @param AbstractDb|int|string $groupOrDb
     */
    protected function _setGroup($groupOrDb)
    {
        $isInt = false;
        if($groupOrDb instanceof AbstractDb)
        {
            $group = $groupOrDb->getTable();
        }
        elseif(is_string($groupOrDb) && !is_numeric($groupOrDb))
        {
            $group = $groupOrDb;
        }
        else
        {
            $isInt = true;
            $group = $groupOrDb;
        }

        #
        $dbGroup = $this->getDbGroup();

        if(!$isInt)
        {
            $where = $this->getWhere()
                ->equalTo('name', $group);

            $row = $dbGroup->findBy($where)->current();
            if(!$row)
            {
                $in = array(
                    'name' => $group
                );

                $dbGroup->doInsert($in);
                $groupId = $dbGroup->getLastInsertValue();
            }
            else
            {
                $groupId = $row->id;
            }
        }
        else
        {
            $row = $dbGroup->findByPrimarykey($group);
            if(!$row)
            {
                throw new \Exception("Group '$group' not found in closure table");
            }

            $groupId = $group;
        }

        $this->groupId = $groupId;
        return $this;
    }


    /**
     * 
     */
    protected function _defaultGroup()
    {
        if(!$this->groupId)
        {
            $groupOrDb = $this->getDbCategory();
            $this->_setGroup($groupOrDb);
        }

        return $this;
    }


    protected function _array_pluck($array, $key)
    {
        return ArrayUtils::pluck($array, $key);
    }


    protected function _checkEntity(AbstractEntity $entity, AbstractDb $dbCategory)
    {
        $dbCategory = $this->getDbCategory();
        $cEntity = $dbCategory->getEntity();

        $cClass = get_class($cEntity);
        if($cClass != get_class($entity))
        {
            throw new \Exception("The entity provided is not of type '$cClass'");
        }
    }
}
