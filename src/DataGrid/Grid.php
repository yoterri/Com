<?php

namespace Com\DataGrid;

use Zend;
use Zend\Paginator\Adapter\NullFill;
use Zend\Paginator\Adapter\AdapterInterface;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Db\Sql\Select;
use Zend\Db\Adapter\Adapter;
use Zend\EventManager\EventManager;
use Zend\EventManager\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventManagerAwareInterface;
use Com\ContainerAwareInterface;
use Interop\Container\ContainerInterface;

class Grid implements EventManagerAwareInterface, ContainerAwareInterface
{
    
    /**
     * @var array
     */
    protected $_mainContainerAttr = array(
        'class' => 'grid-panel',
    );


    /**
     * @var array
     */
    protected $_tableContainerAttr = array(
        'class' => 'table-panel',
    );


    /**
     * @var array
     */
    protected $params = array();


    /**
     * @var array
     */
    protected $_tableAttr = array(
        'class' => 'table table-striped table-bordered' #
        ,'cellspacing' => '0'
        ,'width' => '100%'
        ,'border' => '0'
    );


    /**
     * @var array
     */
    protected $_paginatorConfig = array(
        'container' => array('class' => 'pagination-container pull-right'),
        'paginator' => array('class' => 'pagination'),
        'info' => array('class' => 'pull-right'),
        'li' => array('class' => ''),
        'a' => array(),
        'current' => 'active',
        'disabled' => 'disabled',

        'titles' => array(
            'first' => 'First Page',
            'previous' => 'Previous Page',

            'next' => 'Next Page',
            'last' => 'Last Page',
        ),

        'labels' => array(
            'first' => '<span><<</span>',
            'previous' => '<span><</span>',

            'next' => '<span>></span>',
            'last' => '<span>>></span>',          
        ),

        'templates' => array(
            'paginator_info' => '<div class="">Showing records from {records_from} to {records_to} out of {records_total} <br> Page {page_current} out of {page_total}<br></div>'
        ),
    );


    /**
     * @var array
     */
    protected $_headerAttr = array(
        
    );


    /**
     * @var bool
     */
    protected  $_autoGenerateColumns = false;

    /**
     * @var bool
     */
    protected  $_showPagination = true;

    /**
     * @var string
     */
    protected  $_paginationPosition = 'bottom'; // top/bottom/both/none

    /**
     * @var int
     */
    protected  $_defaultLimit = 25;
   
    /**
     * @var string
     */
    protected  $_gridName = '';

    /**
     * @var bool
     */
    protected  $_showHeader = true;
    
    /**
     * @var array
     */
    protected  $_columns = array();
    
    /**
     * @var string
     */
    protected  $_currentPath  = '';

    /**
     * @var arraya
     */
    protected $_query;

    /**
     * @var int
     */
    protected  $_pageNumber = 1;

    /**
     * @var string
     */
    protected $_sortVarName = 'sort';

    /**
     * @var string
     */
    protected $_orderVarName = 'order';

    /**
     * @var string
     */
    protected $_pageVarName = 'page';

    /**
     * @var string
     */
    protected $_limitVarName = 'limit';

    /**
     * @var array
     */
    protected $_sortBy = array();

    /**
     * @var Zend\Paginator\Paginator
     */
    protected $_paginator;

    /**
     * @var EventManagerInterface
     */
    protected $_eventManager;

    /**
     * @var ContainerInterface
     */
    protected $container;

    
    
    /**
     * @param string $gridName
     */   
    public function __construct($gridName = null)
    {
        $this->_currentPath = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

        if(isset($_SERVER['QUERY_STRING']))
        {
            $this->_currentPath = str_replace($_SERVER['QUERY_STRING'], '', $this->_currentPath);
        }

        if(strpos($this->_currentPath, '?') !== false)
        {
            $this->_currentPath = str_replace('?', '', $this->_currentPath);
        }

        $this->setQueryParams($_GET);
        $this->setGridName($gridName);
    }


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


    /**
     * @param string $key
     * @param mixed $value
     */
    function addParam($key, $value)
    {
        $this->params[$key] = $value;
        return $this;
    }


    /**
     * @param array $data
     */
    function addParams(array $data)
    {
        foreach($data as $key => $value)
        {
            $this->addParam($key, $value);
        }
        
        return $this;
    }


    /**
     * @return array
     */
    function getParams()
    {
        return $this->params;
    }


    /**
     * @param string $key
     * @param string $def
     * @return mixed
     */
    function getParam($key, $def = null)
    {
        if(isset($this->params[$key]))
        {
            $def = $this->params[$key];
        }

        return $def;
    }


    /**
     * @param array $config
     */
    function setPaginatorConfig(array $config)
    {

        if(isset($config['container']) && is_array($config['container']))
        {
            $this->_paginatorConfig['container'] = $config['container'];
        }

        if(isset($config['paginator']) && is_array($config['paginator']))
        {
            $this->_paginatorConfig['paginator'] = $config['paginator'];
        }

        if(isset($config['templates']) && is_array($config['templates']))
        {
            $this->_paginatorConfig['templates'] = $config['templates'];
        }        

        if(isset($config['li']) && is_array($config['li']))
        {
            $this->_paginatorConfig['li'] = $config['li'];
        }

        if(isset($config['a']) && is_array($config['a']))
        {
            $this->_paginatorConfig['a'] = $config['a'];
        }

        if(isset($config['labels']) && is_array($config['labels']))
        {
            $this->_paginatorConfig['labels'] = $config['labels'];
        }

        if(isset($config['titles']) && is_array($config['titles']))
        {
            $this->_paginatorConfig['titles'] = $config['titles'];
        }

        if(isset($config['current']))
        {
            $this->_paginatorConfig['current'] = $config['current'];
        }

        if(isset($config['disabled']))
        {
            $this->_paginatorConfig['disabled'] = $config['disabled'];
        }
    }


    /**
     * @return array 
     */
    function getPaginatorConfig()
    {
        return $this->_paginatorConfig;
    }


    /**
     * @param array $params
     */
    function setQueryParams(array $params)
    {
        $this->_query = $params;
        return $this;
    }


    /**
     * @return array
     */
    function getQueryParams()
    {
        return $this->_query;
    }



    /**
     * @param $eventManager EventManagerInterface
     */
    function setEventManager(EventManagerInterface $eventManager)
    {
        $eventManager->addIdentifiers(array(
            get_called_class()
        ));
    
        $this->_eventManager = $eventManager;
        
        # $this->getEventManager()->trigger('sendTweet', null, array('content' => $content));
        return $this;
    }


    /**
     * @return EventManagerInterface
     */
    public function getEventManager()
    {
        if(!$this->_eventManager)
        {
            $this->setEventManager(new EventManager());
        }

        return $this->_eventManager;
    }


    /**
     * @param int $limit
     */
    function setDefaultLimit($limit)
    {
        $this->_defaultLimit = abs((int)$limit);
        return $this;
    }


    /**
     * @return string
     */
    function getDefaultLimit()
    {
        return $this->_defaultLimit;
    }



    /**
     * @param string $val
     */
    function setPageVarName($val)
    {
        if(empty($val))
        {
            throw new \Exception("Can't be mpty");
        }
            
        $this->_pageVarName = $val;
        return $this;
    }

    
    /**
     * @return string
     */
    function getPageVarName()
    {
        return $this->_pageVarName;
    }


    /**
     * @param string $val
     */
    function setSortVarName($val)
    {
        if(empty($val))
        {
            throw new \Exception("Can't be mpty");
        }
            
        $this->_sortVarName = $val;
        return $this;
    }

    
    /**
     * @return string
     */
    function getSortVarName()
    {
        return $this->_sortVarName;
    }


    /**
     * @param string $val
     */
    function setOrderVarName($val)
    {
        if(empty($val))
        {
            throw new \Exception("Can't be mpty");
        }
            
        $this->_sortVarName = $val;
        return $this;
    }

    
    /**
     * @return string
     */
    function getOrderVarName()
    {
        return $this->_orderVarName;
    }


    /**
     * @param string $val
     */
    function setLimitVarName($val)
    {
        if(empty($val))
        {
            throw new \Exception("Can't be mpty");
        }
            
        $this->_limitVarName = $val;
        return $this;
    }


    /**
     * @return string
     */
    function getLimitVarName()
    {
        return $this->_limitVarName;
    }



    /**
     * @param string $gridName
     */
    function setGridName($gridName)
    {
        $this->gridName = $gridName;
        return $this;
    }


    /**
     * @return string
     */
    function getGridName()
    {
        return $this->gridName;
    }
    
   
    /**
     *
     * @param string $property
     * @param array $config
     */
    function setConfigs($property, array $config)
    {
        foreach($config as $key => $val)
        {
            $this->setConfig($property, $k, $val);
        }

        return $this;
    }
    

    /**
     * @param string $property
     * @param string $key
     * @param mixed $value
     */
    function setConfig($property, $key, $value)
    {
        $var = $this->_getProp($property);

        if(empty($value))
        {
            $tmp = $this->$var;
            if(isset($tmp[$key]))
            {
                unset($tmp[$key]);

                $this->$var = $tmp;
            }
        }
        else
        {
            $tmp = $this->$var;
            $tmp[$key] = $value;

            $this->$var = $tmp;
        }
        
        return $this;
    }


    /**
     * @return string
     */
    function getConfig($property, $key)
    {
        $var = $this->_getProp($property);
        if(isset($this->$var[$key]))
        {
            return isset($this->$var[$key]);
        }
    }


    /**
     * @return array
     */
    function getConfigs($property)
    {
        $var = $this->_getProp($property);
        return $this->$var;
    }



    /**
     * @param array $columns
     */
    function setColumns(array $columns = array())
    {
        $this->_columns = $columns;

        if($this->_autoGenerateColumns)
        {
            if($this->_dataset && count($this->_dataset) > 0)
            {
                $auto_columns = $this->_columns;
                foreach($this->_dataset[0] as $field=>$c)
                {
                    if(!isset($auto_columns[$field]))
                    {
                        $auto_columns[$field] = array('header' => $field, 'type' => 'label');
                    }
                }

                $this->_columns = $auto_columns;
            }
        }

        return $this;
    }


    /**
     * @param bool $var
     */
    function setAutoGenerateColumns($val)
    {
        $this->_autoGenerateColumns = (bool)$val;
        return $this;
    }


    /**
     * @return bool
     */
    function getAutoGenerateColumns()
    {
        return $this->_autoGenerateColumns;
    }


    /**
     * @param bool $var
     */
    function setShowPagination($val)
    {
        $this->_showPagination = (bool)$val;
    }


    /**
     * @return bool
     */
    function getShowPagination()
    {
        return $this->_showPagination;
    }


    /**
     * @param bool $var
     */
    function setShowHeader($val)
    {
        $this->_showHeader = (bool)$val;
    }


    /**
     * @return bool
     */
    function getShowHeader()
    {
        return $this->_showHeader;
    }


    /**
     * @param string $val top|bottom|both|none
     */
    function setPaginationPosition($val)
    {
        $this->_paginationPosition = $val;
        return $this;
    }


    /**
     * @return string
     */
    function getPaginationPosition()
    {
        return $this->_paginationPosition;
    }


    /**
     *
     * @param Select $select
     * @param Adapter $dbAdapter
     */        
    function setSelect(Select $select, Adapter $dbAdapter)
    {
        $adapter = new DbSelect($select, $dbAdapter);
        $this->setAdapter($adapter);
        return $this;
    }

    
    /**
     *
     * @param AdapterInterface $adapter
     */        
    public function setAdapter(AdapterInterface $adapter)
    {
        $this->_paginator = new Paginator($adapter);
        return $this;
    }


    /**
     * @return Zend\Paginator\Paginator
     */
    function getPaginator()
    {
        if(!$this->_paginator)
        {
            $this->_paginator = new Paginator(new NullFill());
        }

        return $this->_paginator;
    }


    /*
     *
     */
    function build()
    {
        $gridName = $this->getGridName();

        $this->_buildColumns();

        $this->_setOrder();

        $this->_buildDatasource();

        #
        $pageKey = "{$gridName}{$this->getPageVarName()}";
        $pageNumber = isset($_GET[$pageKey]) ? $_GET[$pageKey] : 1;
        $this->getPaginator()->setCurrentPageNumber($pageNumber);

        #
        $pageKey = "{$gridName}{$this->getLimitVarName()}";
        $limitNumber = isset($_GET[$pageKey]) ? $_GET[$pageKey] : $this->getDefaultLimit();
        $this->getPaginator()->setItemCountPerPage($limitNumber);

        return $this;
    }


    /**
     * @param string $def
     * @param Select $select
     * @return Select
     */
    protected function _applyOrder($def, Select $select)
    {
        if(count($this->_sortBy))
        {
            $select->reset('order');
            foreach($this->_sortBy as $col => $order)
            {
                $select->order("$col $order");
            }
        }
        else
        {
            if(!empty($def))
            {
                $select->order($def);
            }
        }

        return $select;
    }
    

    /**
     * render
     * 
     * This function generate the html for the grid and return as string
     * 
     * @return  string
     */    
    public function render()
    {
        $paginator = $this->rederPaginator();

        $position = $this->getPaginationPosition();

        $html = '';
        $html .= sprintf('<div%s>', $this->_attrToStr($this->_mainContainerAttr));
        $html .= (('top' == $position) || ('both' == $position)) ? $paginator : '';

        $html .= sprintf('<div%s>', $this->_attrToStr($this->_tableContainerAttr));

        $html .= sprintf('<table%s>', $this->_attrToStr($this->_tableAttr));
        $html .= $this->_renderHeader();
        $html .= $this->_renderRows();
        $html .= '</table>';

        $html .= '</div>';

        $html .= (('bottom' == $position) || ('both' == $position)) ? $paginator : '';

        $html .= '</div>';
        
        return $html;
    }


    /**
     * @param array $arr
     * @return string
     */
    protected function _attrToStr(array $arr)
    {
        $attr = array();
        foreach($arr as $key => $value)
        {
            $attr[] = sprintf('%s="%s"', $key, $value);
        }

        if($attr)
        {
            return ' ' . implode(' ', $attr);
        }
    }

    
    
        
    /**
     * _renderHeader
     * 
     * Generate html for the grid header
     *
     * @return  string
     */
    protected  function _renderHeader()
    {
        if(!$this->getShowHeader())
        {
            return '';
        }
         
        $html = '';
        $html .= '<thead>';
        $html .= sprintf('<tr%s>', $this->_attrToStr($this->_headerAttr));
        $counter = 0;
        foreach($this->_columns as $field => $config)
        {
            $header = isset($config['header']) ? $config['header'] : array();
            
            #
            $eventParams = array(
                'field' => $field,
                'header' => $header,
            );

            $event = $this->_triggerEvent('grid.render_header', $eventParams);

            $header = (array)$event->getParam('header');

            $label = isset($header['label']) ? $header['label'] : '';
            $attributes = isset($header['attributes']) ? (array)$header['attributes'] : array();

            if(isset($attributes['class']))
            {
                $attributes['class'] .= " th_{$counter}";
            }
            else
            {
                $attributes['class'] = "th_{$counter}";
            }

            $sort = isset($header['sort']) ? (bool)$header['sort'] : false;

            $html .= sprintf('<th%s>', $this->_attrToStr($attributes));
            if($sort)
            {
                $order = 'asc';
                $currentPath = $this->_currentPath;
                $params = $this->getQueryParams();
                $gridName = $this->getGridName();
                $currSortField = '';

                $sortKey = "{$gridName}{$this->getSortVarName()}";
                if(isset($params[$sortKey]))
                {
                    $currSortField = $params[$sortKey];
                    unset($params[$sortKey]);
                }

                $orderKey = "{$gridName}{$this->getOrderVarName()}";
                if(isset($params[$orderKey]))
                {
                    if('asc' == $params[$orderKey])
                    {
                        $order = 'desc';
                    }
                    
                    unset($params[$orderKey]);
                }

                $params['sort'] = $field;
                $params['order'] = $order;

                $href = $currentPath . '?' . http_build_query($params);

                $icon = '<i class="fa fa-sort"></i>';

                if(('asc' == $order) && ($field == $currSortField))
                {
                    $icon = '<i class="fa fa-sort-desc"></i>';
                }
                elseif(('desc' == $order) && ($field == $currSortField))
                {
                    $icon = '<i class="fa fa-sort-asc"></i>';
                }

                $html .= sprintf('<a href="%s">%s %s</a>', $href, $label, $icon);
            }
            else
            {
                $html .= $label;
            }
            
            $html .= '</th>';

            $counter++;
        }

        $html .= '</tr>';
        $html .= '</thead>';

        return $html;
    }



    protected function _setOrder()
    {
        $gridName = $this->getGridName();
        $params = $this->getQueryParams();

        $theColumn = array();
        $order = 'asc';

        $orderKey = "{$gridName}{$this->getOrderVarName()}";
        if(isset($params[$orderKey]))
        {
            if('asc' == $params[$orderKey])
            {
                $order = 'asc';
            }
            else
            {
                $order = 'desc';
            }
        }


        $sortKey = "{$gridName}{$this->getSortVarName()}";
        if(isset($params[$sortKey]))
        {
            $index = $params[$sortKey];
            if(isset($this->_columns[$index]))
            {
                if(isset($this->_columns[$index]['header']))
                {
                    if(isset($this->_columns[$index]['header']['sort']) && $this->_columns[$index]['header']['sort'])
                    {
                        if(isset($this->_columns[$index]['header']['sort_column']))
                        {
                            $colmuns = $this->_columns[$index]['header']['sort_column'];
                            if(!is_array($colmuns))
                            {
                                $colmuns = array($colmuns);
                            }

                            foreach($colmuns as $column)
                            {
                                $theColumn[$column] = $order;
                            }
                        }
                    }
                }
            }
        }

        $this->_sortBy = $theColumn;

        return $this;
    }


    /**
     * @return Event
     */
    protected function _triggerEvent($name, array $eventParams)
    {
        $event = new Event($name, $this, $eventParams);
        $this->getEventManager()->triggerEvent($event);

        return $event;
    }
    

    /**
     * @return  string
     */    
    protected  function _renderRows()
    {
        $html = '';
        $dataset = (array)$this->_paginator->getCurrentItems();

        $eventParams = array(
            'rowset' => $dataset,
        );

        $event = $this->_triggerEvent('grid.rowset_current_page', $eventParams);

        $rowset = (array)$event->getParam('rowset');

        foreach($rowset as $index => $row)
        {
            $attributes = array();

            #
            $eventParams = array(
                'attributes' => $attributes,
                'index' => $index,
                'row' => $row,
            );

            $event = $this->_triggerEvent('grid.render_row', $eventParams);
            $attributes = (array)$event->getParam('attributes');

            if(isset($attributes['class']))
            {
                $attributes['class'] .= " tr_{$index}";
            }
            else
            {
                $attributes['class'] = "tr_{$index}";
            }

            $row = $event->getParam('row');

            #
            $html .= sprintf('<tr%s>', $this->_attrToStr($attributes));

            $counter = 0;
            foreach ($this->_columns as $field => $config)
            {
                $cellConfig = isset($config['cell']) ? (array)$config['cell'] : array();
                
                $html .= $this->_renderRow($field, $cellConfig, $row, $counter);
                $counter++;
            }

            $html .= '</tr>';
        }

        return $html;
    }

    
    /**
     * @return  string
     */    
    protected function _renderRow($field, $config, $row, $index)
    {
        #
        $eventParams = array(
            'cell' => $config,
            'field' => $field,
            'row' => $row,
        );

        $event = $this->_triggerEvent('grid.render_cell', $eventParams);
        $config = (array)$event->getParam('cell');

        $value = isset($row[$field]) ? $row[$field] : null;
        if(isset($config['strip_tags']) && (true == $config['strip_tags']))
        {
            if(isset($row[$field]))
            {
                $value = strip_tags($value);
            }
        }

        $attributes = isset($config['attributes']) ? (array)$config['attributes'] : array();

        if(isset($attributes['class']))
        {
            $attributes['class'] .= " td_{$index}";
        }
        else
        {
            $attributes['class'] = "td_{$index}";
        }

        $type = isset($config['type']) ? $config['type'] : null;
        $row = $event->getParam('row');

        #
        $html = '';
        $html .= sprintf('<td%s>', $this->_attrToStr($attributes));

        switch($type)
        {
            case 'custom':

                $value = isset($config['data']) ? $config['data'] : null;

            break;

           
            case 'html':

                $value = '<code>' . htmlentities($value) . '</code>';

            break;
            
            case 'code':

                $value = '<pre>' . $value . '</pre>';

            break;
            
            case 'enum':

                $value = (isset($config['source']) && is_array($config['source']) && isset($config['source'][$value])) ? $config['source'][$value] : $value;

            break;
            
            /*
            case 'progressbar':

                $field_maximum_value = isset($config['maximum_value']) ? $config['maximum_value'] : 100;
                $show_value = isset($config['show_value']) ? $config['show_value'] : false;
                $style = isset($config['style']) ? $config['style'] : 'progress-bar-default';
                $progress_value = ($value/$field_maximum_value * 100);
                if($show_value !== false){
                    $html .= '<div class="clearfix">
                                <small class="pull-left">'.(($value > 0) ? $value : "").'</small>
                              </div>';    
                }
                $html .= '<div class="progress  xs" style="height: 8px;" title="'.$value.'">
                            <div class="progress-bar '.$style.'" role="progressbar" aria-valuenow="'.$progress_value.'" aria-valuemin="0" aria-valuemax="'.$field_maximum_value.'" style="width: '.$progress_value.'%;"></div>
                          </div>';
            break;
            */
                
            case 'date':

                $format_to = isset($config['date_format_to']) ? $config['date_format_to'] : 'F d, Y';
                $format_from = isset($config['date_format_from']) ? $config['date_format_from'] : 'Y-m-d';
                $def = isset($config['empty_date']) ? $config['empty_date'] : '-';

                if($value)
                {
                    $value = $this->_getDateFormated($value, $format_from, $format_to);
                }
                else
                {
                    $value = '-';
                }

            break;
            
            case 'relative_date':

                $value = $this->_getRelativeDate($value);

            break;
            
            case 'money': 

                $field_money_sign = isset($config['sign']) ? $config['sign'] : '$';
                $field_decimal_places = isset($config['decimal_places']) ? $config['decimal_places'] : 2;
                $field_dec_separator = isset($config['decimal_separator']) ? $config['decimal_separator'] : '.';
                $field_thousands_separator = isset($config['thousands_separator']) ? $config['thousands_separator'] : ',';

                $value = $field_money_sign . number_format($value, $field_decimal_places, $field_dec_separator, $field_thousands_separator);

            break;  
            
            case 'password':
            case 'mask':

                $field_symbol = isset($config['symbol']) ? $config['symbol'] : '*';
                $value = str_repeat($field_symbol, strlen($value));

            break;
        }

        if(isset($config['callback']) && is_callable($config['callback']))
        {
            $html .= call_user_func($config['callback'], $value, (array)$row, $field, $config);
        }
        else
        {
            $html .= $value;
        }

        $html .= '</td>';

        return $html;
    }


    protected function _createLink($type, $current=false, $active=true)
    {
        $params = $this->getQueryParams();
        $gridName = $this->getGridName();

        $pageKey = "{$gridName}{$this->getPageVarName()}";

        if(isset($params[$pageKey]))
        {
            unset($params[$pageKey]);
        }

        #
        $config = $this->_paginatorConfig;
        $currentPath = $this->_currentPath;

        #
        $paginator = $this->getPaginator();
        $pages = $paginator->getPages();


        $labels = $config['labels'];
        $titles = $config['titles'];

        $currentClass = $config['current'];
        $disabledClass = $config['disabled'];

        if(isset($pages->$type))
        {
            $params[$pageKey] = $pages->$type;
        }
        else
        {
            $params[$pageKey] = $type;
        }
        
        $href = $currentPath . '?' . http_build_query($params);
        
        
        $liAttr = $config['li'];
        if(!isset($liAttr['class']))
        {
            $liAttr['class'] = '';
        }

        if(!$active)
        {
            $liAttr['class'] .= " $disabledClass";
        }

        if($current)
        {
            if(is_numeric($type))
            {
                $cls = str_replace($disabledClass, '', $liAttr['class']);
                $liAttr['class'] = "$cls $currentClass";
            }
            else
            {
                $liAttr['class'] .= " $currentClass";
            }
        }


        #
        $aAttr = $config['a'];
        $aAttr['href'] = $href;

        if(isset($titles[$type]))
        {
            $aAttr['title'] = $titles[$type];
        }

        if(isset($labels[$type]))
        {
            $label = $labels[$type];
        }
        else
        {
            $label = $type;
        }

        $liAttr = $this->_attrToStr($liAttr);
        $aAttr = $this->_attrToStr($aAttr);
        
        return sprintf('<li%s><a%s>%s</a></li>', $liAttr, $aAttr, $label);
    }



    /**
     * @return string
     */
    function rederPaginator()
    {
        $html = '';
        if($this->getShowPagination())
        {
            #
            $config = $this->_paginatorConfig;

            $paginator = $this->getPaginator();
            $pages = $paginator->getPages();

            # $itemsPerPage = $pages->itemCountPerPage;
            
            $html = '';
            $html .= sprintf('<div%s>', $this->_attrToStr($config['container']));         

            #
            $infoTemplate = '';
            if(isset($config['templates']['paginator_info']))
            {
                $search = array(
                    '{records_from}' => $pages->firstItemNumber,
                    '{records_to}' => $pages->lastItemNumber,
                    '{records_total}' => $pages->totalItemCount,

                    '{page_current}' => $pages->current,
                    '{page_total}' => $pages->pageCount,
                );

                $infoTemplate = str_replace(array_keys($search), array_values($search), $config['templates']['paginator_info']);
            }


            $html .= $infoTemplate;
            $html .= sprintf('<ul%s>', $this->_attrToStr($config['paginator']));
            
            if(isset($pages->first) && ($pages->first != $pages->current))
            {
                $type = 'first';
                $current = 0;
                $active = 1;
                $html .= $this->_createLink($type, $current, $active);
            }
            else
            {
                $type = 'first';
                $current = 1;
                $active = 0;
                $html .= $this->_createLink($type, $current, $active);
            }


            if(isset($pages->previous))
            {
                $type = 'previous';
                $current = 0;
                $active = 1;
                $html .= $this->_createLink($type, $current, $active);
            }
            else
            {
                $type = 'previous';
                $current = 1;
                $active = 0;
                $html .= $this->_createLink($type, $current, $active);
            }

            if(isset($pages->pagesInRange))
            {
                foreach($pages->pagesInRange as $page)
                {
                    if($page == $pages->current)
                    {
                        $type = $page;
                        $current = 1;
                        $active = 0;
                        $html .= $this->_createLink($type, $current, $active);
                    }
                    else
                    {
                        $type = $page;
                        $current = 0;
                        $active = 1;
                        $html .= $this->_createLink($type, $current, $active);
                    }
                }
            }

            if(isset($pages->next))
            {
                $type = 'next';
                $current = 0;
                $active = 1;
                $html .= $this->_createLink($type, $current, $active);
            }
            else
            {
                $type = 'next';
                $current = 1;
                $active = 0;
                $html .= $this->_createLink($type, $current, $active);
            }

            if(isset($pages->last) && ($pages->last != $pages->current))
            {
                $type = 'last';
                $current = 0;
                $active = 1;
                $html .= $this->_createLink($type, $current, $active);
            }
            else
            {
                $type = 'last';
                $current = 1;
                $active = 0;
                $html .= $this->_createLink($type, $current, $active);
            }

            $html .= '</ul>';
            $html .= '</div>';
        }

        return $html;
    }


    
    # UTILITY METHODS

    /**
     * @return string
     */
    protected function _getProp($key)
    {
        if('main-container' == $key)
        {
            return '_mainContainerAttr';
        }

        elseif('table-container' == $key)
        {
            return '_mainContainerAttr';
        }

        elseif('table' == $key)
        {
            return '_tableAttr';
        }

        elseif('header' == $key)
        {
            return '_headerAttr';
        }

        throw new \Exception("Config property '$key' not found.");
        
    }
    
                    
    /**
     * _strReplaceFirst
     * 
     * Replaces only first occurrences of the provided text 
     *
     * @param   string  $from
     * @param   string  $to
     * @param   string  $subject
     * @return  string
     */ 
    protected  function _strReplaceFirst($from, $to, $subject)
    {
        $from = '/'.preg_quote($from, '/').'/';
        return preg_replace($from, $to, $subject, 1);
    }
    

    /**
     * _getRelativeDate
     * 
     * Get the relative date string
     * expect parameter as timestamp integer or a date string 
     *
     * @param   mixed   $ts
     * @return  string
     */   
    protected  function _getRelativeDate($ts) 
    {
        if(empty($ts)) { return ''; }
        
        $ts = (!ctype_digit($ts)) ? strtotime($ts) : $ts;

        $diff = time() - $ts;
        if($diff == 0)
        {
            return 'now';
        }
        elseif($diff > 0)
        {
            $day_diff = floor($diff / 86400);
            if($day_diff == 0)
            {
                if($diff < 60) 
                {
                    return 'just now';
                }

                if($diff < 120) 
                {
                    return '1 minute ago';
                }


                if($diff < 3600) 
                {
                    return floor($diff / 60) . ' minutes ago';
                }

                if($diff < 7200)
                {
                    return '1 hour ago';
                }


                if($diff < 86400)
                {
                    return floor($diff / 3600) . ' hours ago';
                }
            }

            if($day_diff == 1)
            { 
                return 'Yesterday';
            }

            if($day_diff < 7)
            {
                return $day_diff . ' days ago';
            }

            if($day_diff < 31)
            {
                $week = ceil($day_diff / 7); return $week . ' week'.(($week == 1) ? '' :'s').' ago';
            }

            if($day_diff < 60)
            {
                return 'last month';
            }

            return date('F d, Y', $ts);
        }
        else
        {
            $diff = abs($diff);
            $day_diff = floor($diff / 86400);
            if($day_diff == 0)
            {
                if($diff < 120)
                {
                    return 'in a minute';
                }

                if($diff < 3600)
                {
                    return 'in ' . floor($diff / 60) . ' minutes';
                }

                if($diff < 7200)
                {
                    return 'in an hour';
                }

                if($diff < 86400)
                {
                    return 'in ' . floor($diff / 3600) . ' hours';
                }
            }

            if($day_diff == 1)
            {
                return 'Tomorrow';
            }

            if($day_diff < 4)
            {
                return date('l', $ts);
            }

            if($day_diff < 7 + (7 - date('w')))
            {
                return 'next week';
            }

            if(ceil($day_diff / 7) < 4)
            {
                $week = ceil($day_diff / 7); 
                return 'in ' . $week . ' week'.(($week == 1) ? '' :'s');
            }

            if(date('n', $ts) == date('n') + 1)
            {
                return 'next month';
            }

            return date('F d, Y', $ts);
        }
    }
    
    /**
     * _getDateFormated
     * 
     * Convert date from one format to another 
     *
     * @param   string  $date_str
     * @param   string  $format_from
     * @param   string  $format_to
     * @return  string
     */   
    protected function _getDateFormated($date_str, $format_from = "Y-m-d H:i:s", $format_to = "Y-m-d H:i:s")
    {
        if(empty($date_str)) 
        {
            return '';
        }
        
        $date_array = date_parse_from_format($format_from, $date_str);
        $timestamp = mktime($date_array['hour'], $date_array['minute'], $date_array['second'], $date_array['month'], $date_array['day'], $date_array['year']);
        return date($format_to, $timestamp);
    }
    
    
    /**
     * get_microtime
     * 
     * Returns the current Unix timestamp with microseconds 
     *
     * @return  float
     */    
    function _getMicrotime()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }
    
    /**
     * get_microtime_formated
     * 
     * Format microtime to datetime
     *
     * @return  datetime
     */    
    function _getMicrotimeFormated($microtime)
    {
        //list($usec, $sec) = explode(' ', microtime()); //split the microtime on space, with two tokens $usec and $sec
        list($sec, $usec) = explode('.', $microtime);
        $usec = str_replace("0.", ".", $usec); //remove the leading '0.' from usec
        return date('H:i:s', $sec) . round($usec, 6);
    }


    protected function _buildDatasource()
    {

    }

    protected function _buildColumns()
    {

    }
}