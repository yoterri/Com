<?php
namespace Com\Db\Priv;

use Com\Db\AbstractDb;
use Com\LazyLoadInterface;

class UserHasRole extends AbstractDb implements LazyLoadInterface
{
    protected $tableName = 'priv_user_has_role';
    protected $entityClassName = 'Com\Entity\Priv\UserHasRole';
}
