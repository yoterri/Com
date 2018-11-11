<?php
namespace Com\Db\Priv;

use Com\Db\AbstractDb;
use Com\LazyLoadInterface;

class RoleHasCapability extends AbstractDb implements LazyLoadInterface
{
    protected $tableName = 'priv_role_has_capability';
    protected $entityClassName = 'Com\Entity\Priv\RoleHasCapability';
}
