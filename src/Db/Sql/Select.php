<?php

namespace Com\Db\Sql;

use Zend\Db\Adapter\Driver\DriverInterface;
use Zend\Db\Adapter\ParameterContainer;
use Zend\Db\Adapter\Platform\PlatformInterface;

use Zend\Db\Sql\Select as zSelect;

class Select extends zSelect
{


    protected function processLimit(
        PlatformInterface $platform,
        DriverInterface $driver = null,
        ParameterContainer $parameterContainer = null
    ) {
        if ($this->limit === null) {
            return;
        }

        if ($parameterContainer) {
            $parameterContainer->offsetSet('limit', $this->limit, ParameterContainer::TYPE_INTEGER);
            return [$driver->formatParameterName('limit')];
        }

        if($platform instanceof  \Zend\Db\Adapter\Platform\Mysql)
        {
            return [$this->limit];
        }

        return [$platform->quoteValue($this->limit)];
    }
}

