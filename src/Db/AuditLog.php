<?php
namespace Com\Db;

use Com\Db\AbstractDb;
use Com\LazyLoadInterface;

class AuditLog extends AbstractDb implements LazyLoadInterface
{
    protected $tableName = 'audit_log';
        
}
