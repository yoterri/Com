<?php
namespace Com\Db\Closure;

use Com\Db\AbstractDb;
use Com\LazyLoadInterface;

class Closure extends AbstractDb implements LazyLoadInterface
{
    protected $tableName = 'closure';
    protected $entityClassName = 'Com\Entity\Closure\Closure';
}
