<?php

namespace Com\GridBuilder;

use Com\Component\TinyGrid\Grid;
use Com\LazyLoadInterface;
use Com\ContainerAwareInterface;
use Interop\Container\ContainerInterface;
use Zend\EventManager\EventManager;
use Zend\Escaper\Escaper;
use Zend\Db\Sql\Select;

abstract class AbstractBuilder implements ContainerAwareInterface, LazyLoadInterface
{
    /**
     * @var ContainerInterface
     */    
    protected $container;

    /**
     * @var Grid
     */
    protected $tinyGrid = false;

    /**
     * @param ContainerInterface $container
     * @return Builder
     */
    function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
        return $this;
    }

    /**
     * @return ContainerInterface
     */
    function getContainer()
    {
        return $this->container;
    }


    /**
     * @return TinyGrid
     */
    function getGrid()
    {
        if(!$this->tinyGrid)
        {
            $this->_buildGrid();

            #
            $source = $this->_getSource();
            if($source)
            {
                $dbAdapter = $this->_getAdapter();
                $this->tinyGrid->setSource($source, $dbAdapter);
            }

            $cols = $this->_getColumns();
            if(is_array($cols))
            {
                $this->tinyGrid->setColumns($cols);
            }
        }

        return $this->tinyGrid;
    }


    protected function _buildGrid()
    {
        $this->tinyGrid = new Grid();

        #
        $escaper = new Escaper();
        $this->tinyGrid->setEscaper($escaper);

        $container =$this->getContainer();
        if($container)
        {
            if($container->has('Zend\EventManager\EventManager'))
            {
                $eventManager = $container->get('Zend\EventManager\EventManager');
            }
            elseif($container->has('EventManager'))
            {
                $eventManager = $container->get('EventManager');
            }
            else
            {
                $eventManager = new EventManager();
            }
        }
        else
        {
            $eventManager = new EventManager();
        }


        $this->tinyGrid->setEventManager($eventManager);
    }


    abstract protected function _getSource();


    abstract protected function _getColumns();


    protected function _getAdapter()
    {
        $adapter = null;

        #
        $sm = $this->getContainer();
        if($sm)
        {
            $adapter = $sm->get('adapter');
        }

        #
        return $adapter;
    }
    
}