<?php
namespace Com\Db\Priv;

use Com\Db\AbstractDb;
use Zend\Sql\Db\Select;
use Zend\Sql\Db\Where;
use Com\LazyLoadInterface;

class Capability extends AbstractDb implements LazyLoadInterface
{
    protected $tableName = 'priv_capability';
    protected $entityClassName = 'Com\Entity\Priv\Capability';


    /**
     * @param string|int $role
     * @param string|string[] $capability
     * @return bool
     */
    function roleHasCapability($role, $capability)
    {
    	$role = $this->_getRoleId($role);
    	if(1 == $role)
    	{
    		return true;
    	}

    	if(!is_array($capability))
    	{
    		$capability = [$capability];
    	}
    	
    	return $this->_roleHasAnyCapability($role, $capability);
    }


    /**
     * @param string|int $role
     * @param string[] $capability
     * @return bool
     */
    protected function _roleHasAnyCapability($role, array $capabilities)
    {
    	$role = $this->_getRoleId($role);
    	if(1 == $role)
    	{
    		return true;
    	}

    	$sm = $this->getContainer();

    	$where = $this->getWhere()
    		->equalTo('rc.role_id', $role)
    		->in('c.name', $capabilities);

    	#
    	$dbRoleCapability = $sm->get('Com\Db\Priv\RoleHasCapability'); 	
    	$dbcapability = $this;

    	$select = new Select();

    	$select->from(['c' => $dbcapability]);
    	$select->join(['rc' => $dbRoleCapability], 'rc.capability_id = c.id', []);
    	$select->where($where);

    	return ($this->executeCustomSelect($select)->count() > 0);
    }
    

    protected function _getRoleId($role)
    {
    	if(!is_integer($role))
    	{
    		$row = $this->getContainer()
    			->get('Com\Db\Priv\Role')
    			->findByName($role);

    		if($row)
    		{
    			$role = $row->id;
    		}
    	}

    	return $role;
    }
}
