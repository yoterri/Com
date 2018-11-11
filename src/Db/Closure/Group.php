<?php
namespace Com\Db\Closure;

use Com\Db\AbstractDb;
use Com\LazyLoadInterface;

class Group extends AbstractDb implements LazyLoadInterface
{
    protected $tableName = 'closure_group';
    protected $entityClassName = 'Com\Entity\Closure\Group';
}
