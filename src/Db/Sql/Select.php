<?php

namespace Com\Db\Sql;

use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\ParameterContainer;
use Laminas\Db\Adapter\Platform\PlatformInterface;

use Laminas\Db\Sql\Select as lSelect;

class Select extends lSelect
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

        if($platform instanceof  \Laminas\Db\Adapter\Platform\Mysql)
        {
            return [$this->limit];
        }

        return [$platform->quoteValue($this->limit)];
    }
}

