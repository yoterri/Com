<?php
namespace Com\Db\Closure;

use Com\Db\AbstractDb;
use Com\LazyLoadInterface;

class Category extends AbstractDb implements LazyLoadInterface
{
    protected $tableName = 'closure_category';
    protected $entityClassName = 'Com\Entity\Closure\Category';
}
