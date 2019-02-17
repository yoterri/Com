<?php
namespace Com\Control;
use Com\LazyLoadInterface;
use Zend\Db\ResultSet\AbstractResultSet;
use Com\Db\AbstractDb;
use Zend\Db\Sql\Literal;
use Zend\Db\Sql\Expression;
use Com\Db\Sql\Select;
use Com\ArrayUtils;
use Com\Entity\AbstractEntity;
use Zend\Stdlib\Parameters;
use Com\Filter\ExtraTrim;

class Closure extends AbstractControl implements LazyLoadInterface
{

    /**
     * @var AbstractDb
     */
    protected $dbNode;

    /**
     * @var AbstractDb
     */
    protected $dbSort;

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
    protected $dbNodePrimary = 'id';

    /**
     * @var string
     */
    protected $dbNodeLabel = 'name';




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
        $dbNode = $this->getDbNode();
        $dbSort = $this->getDbSort();

        #
        $cols = $dbNode->getEntity()->getEntityColumns();
        foreach($cols as $key => $col)
        {
            $cols[$col] = new Literal("t.$col");
            unset($cols[$key]);
        }

        $cols += [
            'parent' => new Literal('c2.parent_id')
            ,'path' => new Literal('GROUP_CONCAT(bc.parent_id ORDER BY bc.depth DESC)')
            ,'depth' => new Literal('c1.depth')
            ,'sort' => new Literal('sort.sort')
            #,$alias => new Literal("t.{$column}")
        ];

        #echo '<pre>';
        #print_r($cols);
        #exit;

        #
        $select = new Select();
        $select->columns($cols);

        $select->from(['c1' => $dbClosure]);
        $select->join(['t' => $dbNode], "t.{$this->dbNodePrimary} = c1.child_id", []);
        $select->join(['c2' => $dbClosure], new Literal('c2.depth IN(1) AND c2.child_id = c1.child_id'), [], 'left'); // ugh backticking INTs in #joins @TODO
        $select->join(['bc' => $dbClosure], '(c1.child_id = bc.child_id)', []);
        $select->join(['sort' => $dbSort], new Expression('sort.node_id = t.id AND sort.group_id = ?', $this->groupId), [], 'left');

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
        $select->order('sort.sort ASC');

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
                $id = $this->dbNodePrimary;

                foreach($rowset as $row)
                {
                    $row = $row->toArray();

                    if($row['depth'])
                    {
                        $row['indented'] = str_repeat('-', $row['depth']) . " {$row['name']}";
                    }
                    else
                    {
                        $row['indented'] = $row['name'];
                    }
                    
                    $k = $row[$id];
                    $trees["_{$k}"] = $row;
                }

                foreach($trees as $key => $row)
                {
                    if(!$root)
                    {
                        $root = $row['parent'];
                    }

                    $k = $row['parent'];
                    $trees["_{$k}"]['children'][$key] =& $trees[$key];
                }

                $result = $trees["_{$root}"];

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



    function sort(array $data)
    {
        $flag = false;
        $dbSort = $this->getDbSort();

        #
        $where = array(
            'group_id' => $this->groupId
        );
        $dbSort->doDelete($where);

        #
        $this->_sort($dbSort, $data);

        return true;
    }


    protected function _sort($dbSort, $data, $isChild = false)
    {
        $counter = 1;
        foreach($data as $item)
        {
            if(is_array($item) && isset($item['id']))
            {
                $in = array(
                    'group_id' => $this->groupId,
                    'node_id' => $item['id'],
                    'sort' => $counter,
                );

                $counter++;

                $dbSort->doInsert($in);

                if(isset($item['children']) && count($item['children']))
                {
                    $this->_sort($dbSort, $item['children'], true);
                }
            }
        }
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


     /**
     * @return array
     */
    function getNested()
    {
        return $this->getRecords(true);
    }


    /**
     * @return array
     */
    public function getRecords($nested = false)
    {
        $roots = $this->getRoot() ;

        $result = array();
        foreach($roots as $item)
        {
            $nodeId = $item['id'];
            $result[] = $this->getChildren($nodeId, true, true);
        }

        if(!$nested)
        {
            $result = $this->_unnest($result);
        }

        return $result;
    }


    /**
     * @param array $rowset
     * @return array
     */
    protected function _unnest($rowset)
    {
        $ret = array();

        foreach($rowset as $row)
        {
            if(isset($row['children']))
            {
                $children = $row['children'];
                unset($row['children']);

                $ret[] = $row;

                $x = $this->_unnest($children);
                foreach($x as $item)
                {
                    $ret[] = $item;
                }
            }
            else
            {
                $ret[] = $row;
            }
        }

        return $ret;        
    }


    /**
     * Get (all) root nodes.
     */
    public function getRoot()
    {
        $this->_defaultGroup();

        $dbClosure = $this->getDbClosure();
        $dbNode = $this->getDbNode();
        $dbSort = $this->getDbSort();


        $cols = $dbNode->getEntity()->getEntityColumns();
        foreach($cols as $key => $col)
        {
            $cols[$col] = new Literal("t.$col");
            unset($cols[$key]);
        }

        $cols += [
            'parent' => new Literal('c2.parent_id')
            ,'depth' => new Literal('c1.depth')
            ,'sort' => new Literal('sort.sort')
        ];

        #
        $select = new Select();
        $select->columns($cols);

        $select->from(['c1' => $dbClosure]);
        $select->join(['t' => $dbNode], "t.{$this->dbNodePrimary} = c1.child_id", []);
        $select->join(['c2' => $dbClosure], new Literal('c1.child_id = c2.child_id AND c2.parent_id <> c2.child_id'), [], 'left');
        $select->join(['sort' => $dbSort], new Expression('sort.node_id = t.id AND sort.group_id = ?', $this->groupId), [], 'left');

        $where = $this->getWhere()
            ->isNull('c2.child_id')
            ->equalTo('c1.group_id', $this->groupId);

        $select->where($where);
        $select->order('sort.sort ASC');

        #$dbClosure->debugSql($select);

        $rowset = $dbClosure->executeCustomSelect($select, $this->getContainer()->get('Com\Entity\Record'));

        $result = array();
        if($rowset)
        {
            foreach($rowset as $row)
            {
                $result[] = $row->toArray();
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
        $dbClosure = $this->getDbNode();

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

        return $this->getCommunicator()->isSuccess();
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
     * @param boolean $deleteReference - if true, it will also delete from node table
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

                $this->getDbNode()
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
        $dbNode = $this->getDbNode();

        $connection1 = $dbNode->getDbAdapter()
            ->getDriver()
            ->getConnection();


        try
        {
            if(!$connection1->inTransaction())
            {
                $connection1->beginTransaction();
            }

            $this->_checkEntity($entity, $dbNode);

            if(!$entity->id)
            {
                throw new \Exception('No id value was provided');
            }

            $where = $this->getWhere()
                ->equalTo('id', $entity->id);

            $dbNode->doUpdate($entity->toArray(), $where);


            if(!is_null($targetId))
            {
                $this->move($entity->id, $targetId);
            }


            if($connection1->inTransaction())
            {
                $connection1->commit();
            }

            $this->getCommunicator()->setSuccess('Successfull updated', array('id' => $entity->id));
        }
        catch(\Exception $e)
        {
            $this->getCommunicator()->setException($e);

            if($connection1->inTransaction())
            {
                $connection1->rollback();
            }
        }

        return $this->getCommunicator()->isSuccess();
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
        $dbNode = $this->getDbNode();
        $connection1 = $dbNode->getDbAdapter()
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

            $this->_checkEntity($entity, $dbNode);

            $in = $entity->toArray();

            $dbNode->doInsert($in);
            $nodeId = $dbNode->getLastInsertValue();

            $id = $this->_add($nodeId, $targetId, $dbClosure->getDbAdapter());

            #
            $this->getCommunicator()->setSuccess('Successfull added', array('id' => $id));


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

        return $this->getCommunicator()->isSuccess();
    }



    /**
     * @param array $data
     */
    /*
    function updateTree(array $data)
    {
        $dbNode = $this->getDbNode();
        $connection1 = $dbNode->getDbAdapter()
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

            $entity = $dbNode->getEntity();

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

        #
        $in = array(
            'group_id' => $this->groupId,
            'node_id' => $nodeId,
            'sort' => 999999,
        );
        $this->getDbSort()->doInsert($in);

        #
        return $nodeId;
    }





    /**
     * @return array [description]
     */
    protected function _getLabelColumn()
    {
        $refColumn = $this->dbNodeLabel;

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
     * @return Closure
     */
    function setDbSort(AbstractDb $db)
    {
        $this->dbSort = $db;

        return $this;
    }




    /**
     * @return AbstractDb
     */
    function getDbSort()
    {
        if(!$this->dbSort instanceof AbstractDb)
        {
            $this->dbSort = $this->getContainer()
                ->get('Com\Db\Closure\Sort');

            $this->setDbSort($this->dbSort);
        }

        return $this->dbSort;
    }


    /**
     * @param AbstractDb $db
     * @return Closure
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
     * @return Closure
     */
    function setDbNode(AbstractDb $db, $primaryKey = 'id', $labelColumn = 'name')
    {
        $this->dbNode = $db;
        $this->dbNodePrimary = $primaryKey;
        $this->dbNodeLabel = $labelColumn;
        return $this;
    }

    /**
     * @return AbstractDb
     */
    function getDbNode()
    {
        if(!$this->dbNode instanceof AbstractDb)
        {
            $dbNode = $this->getContainer()
                ->get('Com\Db\Closure\Node');

            $this->setDbNode($dbNode);
        }

        return $this->dbNode;
    }


    /**
     * @param AbstractDb $db
     * @return Closure
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



    function addGroup($name)
    {
        $trim = new ExtraTrim();
        $name = $trim->filter($name);
        $name = trim($name);

        if(!empty($name))
        {
            $in = array(
                'name' => $name
            );

            $dbGroup = $this->getDbGroup();
            if(!$dbGroup->count($in))
            {
                $id = $dbGroup->doInsert($in);
                $this->getCommunicator()->setSuccess('Record successfull added', array('id' => $id));
            }
            else
            {
                $this->getCommunicator()->addError('Already exists a record with the provided Name');
            }
        }
        else
        {
            $this->getCommunicator()->addError('Name is required');
        }

        return $this->getCommunicator()->isSuccess();
    }


    function editGroup($id, $name)
    {
        $trim = new ExtraTrim();
        $name = $trim->filter($name);
        $name = trim($name);

        if(!empty($name))
        {
            $dbGroup = $this->getDbGroup();

            if($dbGroup->findByPrimarykey($id))
            {
                $where = $this->getWhere()
                    ->equalTo('name', $name)
                    ->notEqualTo('id', $id);

                $dbGroup = $this->getDbGroup();
                if(!$dbGroup->count($where))
                {
                    $in = array(
                        'name' => $name
                    );

                    $dbGroup->doUpdate($in, array('id' => $id));
                    $this->getCommunicator()->setSuccess('Record successfull updated', array('id' => $id));
                }
                else
                {
                    $this->getCommunicator()->addError('Already exists a record with the provided Name');
                }
            }
            else
            {
                $this->getCommunicator()->addError('Category not found');
            }
        }
        else
        {
            $this->getCommunicator()->addError('Name is required');
        }

        return $this->getCommunicator()->isSuccess();
    }


    /**
     * 
     */
    protected function _defaultGroup()
    {
        if(!$this->groupId)
        {
            $groupOrDb = $this->getDbNode();
            $this->_setGroup($groupOrDb);
        }

        return $this;
    }


    protected function _array_pluck($array, $key)
    {
        return ArrayUtils::pluck($array, $key);
    }


    protected function _checkEntity(AbstractEntity $entity, AbstractDb $dbNode)
    {
        $dbNode = $this->getDbNode();
        $cEntity = $dbNode->getEntity();

        $cClass = get_class($cEntity);
        if($cClass != get_class($entity))
        {
            throw new \Exception("The entity provided is not of type '$cClass'");
        }
    }
}