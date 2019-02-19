<?php

namespace Com\Validator;

use Zend\Validator\AbstractValidator;
use Com\Control\Closure;
use Zend\Db\Sql\Select;

class ClosureNodeExists extends AbstractValidator
{

	const RECORD_NOT_FOUND = 'record_not_found';

	protected $messageTemplates = array(
        self::RECORD_NOT_FOUND => 'Specified Value was not found in the database',
    );

    /**
     * @var Com\Control\Closure
     */
    protected $cClosure;


    /**
     * @param $closure Com\Control\Closure
     */
    function setClosureControl(Closure $closure)
    {
        $this->cClosure = $closure;
    }

    /**
     * @return $closure Com\Control\Closure
     */
    function getClosureControl()
    {
        return $this->cClosure;
    }


    /**
     * @return bool
     */
    public function isValid($value)
    {
        $cClosure = $this->getClosureControl();

        if(!$cClosure)
        {
            throw new \Exception('Closure control class was not provided');
        }

        #
        $dbNode = $cClosure->getDbNode();
        $dbClosure = $cClosure->getDbClosure();
        $dbGroup = $cClosure->getDbGroup();
        
        #
        $grp = $cClosure->getGroup();

        #
        $select = new Select();
        $select->from(array('n' => $dbNode));
        $select->join(array('c' => $dbClosure), 'c.child_id = n.id', array());
        $select->join(array('cg' => $dbGroup), 'cg.id = c.group_id', array());

        $select->where(function($where) use($grp, $value) {
            $where->equalTo('cg.id', $grp);
            $where->equalTo('n.id', $value);
        });

        $rowset = $dbNode->executeCustomSelect($select);
        $total = $rowset->count();

        #echo "Total: $total<br>";
        #$dbNode->debugSql($select, 0);

        $flag = $total > 0;

        if(!$flag)
        {
			$this->error(self::RECORD_NOT_FOUND);
        }

        return $flag;
    }
}