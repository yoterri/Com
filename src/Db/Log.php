<?php
namespace Com\Db;

use Com\Db\AbstractDb;
use Com\LazyLoadInterface;

class Log extends AbstractDb implements LazyLoadInterface
{
    protected $tableName = 'log';
        
}
