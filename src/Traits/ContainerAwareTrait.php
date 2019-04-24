<?php

namespace Com\Traits;

use Interop\Container\ContainerInterface;

trait ContainerAwareTrait
{
     /**
     * @var ContainerInterface
     */
    protected $container;


    /**
     * @param ContainerInterface $container
     */
    function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    
    /**
     * @return ContainerInterface
     */
    function getContainer()
    {
        return $this->container;
    }
}
