<?php

namespace Com\Injector;

use Interop\Container\ContainerInterface;

class Container
{

    public function __invoke(ContainerInterface $container, $instance)
    {
        $instance->setContainer($container);
    }
}