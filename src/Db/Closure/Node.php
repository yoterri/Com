<?php
namespace Com\Db\Closure;

use Com\Db\AbstractDb;
use Com\LazyLoadInterface;

class Node extends AbstractDb implements LazyLoadInterface
{
    protected $tableName = 'closure_node';
    protected $entityClassName = 'Com\Entity\Closure\Node';
}
