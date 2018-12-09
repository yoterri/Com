<?php
namespace Com\Db\Closure;

use Com\Db\AbstractDb;
use Com\LazyLoadInterface;

class Sort extends AbstractDb implements LazyLoadInterface
{
    protected $tableName = 'closure_sort';
    protected $entityClassName = 'Com\Entity\Closure\Sort';
}
