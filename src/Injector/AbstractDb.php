<?php

namespace Com\Injector;

use Interop\Container\ContainerInterface;

class AbstractDb
{

    public function __invoke(ContainerInterface $container, $instance)
    {
        $entityClassName = $instance->getEntityClassName();
        if($entityClassName)
        {
            $entityClass = $container->build($entityClassName);

            $instance->getResultSetPrototype()
                ->setArrayObjectPrototype($entityClass);
        }

        $instance->initialize();
    }
}