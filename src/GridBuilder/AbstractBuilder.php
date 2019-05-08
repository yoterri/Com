<?php

namespace Com\GridBuilder;

use Com\Component\TinyGrid\Grid;
use Com\Interfaces\LazyLoadInterface;
use Com\Interfaces\ContainerAwareInterface;
use Com\Traits\ContainerAwareTrait;

use Interop\Container\ContainerInterface;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\Escaper\Escaper;
use Zend\Db\Sql\Select;

abstract class AbstractBuilder implements ContainerAwareInterface, EventManagerAwareInterface, LazyLoadInterface
{
    use ContainerAwareTrait, EventManagerAwareTrait;

    /**
     * @var Grid
     */
    protected $tinyGrid = false;

    /**
     * @var Zend\Router\RouteMatch
     */
    protected $routeMatch;

    /**
     * @array
     */
    protected $routes = [];


    /**
     * @return TinyGrid
     */
    function build($basePath = null, array $queryParams = array(), $gridName = null)
    {
        if(!$this->tinyGrid)
        {
            $this->_buildGrid($gridName, $basePath, $queryParams);

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


    /**
     * @param string $routeName
     * @param array $params
     * @param array $options
     * @return string
     */
    function url($routeName, array $params = [], array $options = [])
    {
        $routeMatch = $this->_getRouteMatch();
        if($routeMatch->getMatchedRouteName() == $routeName)
        {
            if(!isset($params['controller']))
            {
                $params['controller'] = $routeMatch->getParam('controller');
            }

            if(!isset($params['module']))
            {
                $params['module'] = $routeMatch->getParam('module');
            }

            if(!isset($params['action']))
            {
                $params['action'] = $routeMatch->getParam('action');
            }
        }

        $route = $this->_getRoute($routeName);
        return $route->assemble($params, $options);
    }


    abstract protected function _getSource();
    abstract protected function _getColumns();


    protected function _buildGrid($gridName = null, $basePath = null, array $queryParams = array())
    {
        $this->tinyGrid = new Grid($basePath, $queryParams, $gridName);

        $eventManager = $this->getEventManager();
        if($eventManager)
        {
            $this->tinyGrid->setEventManager($eventManager);
        }

        #
        $escaper = new Escaper();
        $this->tinyGrid->setEscaper($escaper);

        $this->_init();
    }


    protected function _init()
    {
        ;
    }


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


    /**
     * @return Zend\Router\RouteMatch
     */
    protected function _getRouteMatch()
    {
        if(!$this->routeMatch)
        {
            $sm = $this->getContainer();

            #
            $request = $sm->get('request');
            $router = $sm->get('router');

            #
            $this->routeMatch = $router->match($request);
            #$routeMatch->getMatchedRouteName();
        }

        return $this->routeMatch;
    }


    
    /**
     * @return Zend\Router\Http\RouteInterface
     */
    protected function _getRoute($routeName)
    {
        if(!isset($this->routes[$routeName]))
        {
            $sm = $this->getContainer();
            $router = $sm->get('router');
            $route = $router->getRoute($routeName);

            $this->routes[$routeName] = $route;
        }

        return $this->routes[$routeName];
    }

}