<?php
namespace Com\Db;

use Com\Db\AbstractDb;
use Com\LazyLoadInterface;

class Session extends AbstractDb implements LazyLoadInterface
{
    protected $tableName = 'session';
        
}
