<?php

namespace Com;
use Interop\Container\ContainerInterface;

interface ContainerAwareInterface
{

    function setContainer(ContainerInterface $container);
    
    function getContainer();
    
}