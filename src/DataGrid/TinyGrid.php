<?php

/*
$columns = [
    'id' => [
        'header' => [
            'attributes' => array('style' => 'width:30%'), 

            'label' => 'ID',

            'sort' => true,
            'sort_column' => ['sort_col1', 'sort_col2'],
            'sort_default' => ['desc'],

            'filter' => 1,
            'filter_type' => '=', # > | < | >= | <=
            'filter_column' => ['col_name1', 'col_name2']
            'filter_values' => [
                1 => 'One',
                5 => 'Five',
                6 => 'Six',
            ],
        ],

        'cell' => [
            'type' => 'action',

            #'btn_type' => 'btn-default',
            #'btn_size' => 'btn-xs',

            'data' => function($row, $config) {
                return array(
                    array(
                        #'href' => '#',
                        'label' => '<i class="fa fa-pencil"></i>',
                        #'attributes' => [],
                    ),
                    array(
                        'href' => '#',
                        'label' => 'hola2',
                        #'attributes' => [],
                    ),
                );
            }
        ],


        'cell' => [
            'menu' => function($row, $config) {
                $r = "
                <div class='row-actions'>
                    <a href='lorem'>Ipsum</a> |
                    <a href='lorem'>Ipsum</a> |
                    <a href='lorem'>Ipsum</a> |
                    <a href='lorem'>Ipsum</a> |
                </div>";
                return $r;
            }
        ],

        'cell' => [
            'callback' => function($value, $row, $field, $config) {
                return $value;
            },
        ],

        'cell' => [
            'type' => 'custom',
            'data' => 'The value to be shown',
        ],

        'cell' => [
            'type' => 'number',
        ],

        'cell' => [
            'type' => 'amount',
            'decimal_separator' => '.',
            'thousands_separator' => ','
        ],

        'cell' => [
            'type' => 'html',
        ],

        'cell' => [
            'type' => 'code',
        ],

        'cell' => [
            'type' => 'relative_date',
        ],

        'cell' => [
            'type' => 'password', # 'mask',
            'symbol' => '*',
        ],

        'cell' => [
            'type' => 'enum'
            'source' => [1 => 'One', 5 => 'Five'],
        ],

        'cell' => [
            'type' => 'date',
            'date_format_to' => 'F d, Y',
            'date_format_from' => 'Y-m-d',
            'empty_date' => '-'
        ],

        'cell' => [
            'type' => 'money',
            'sign' => '$',
            'decimal_places' => 2,
            'decimal_separator' => '.',
            'thousands_separator' => ','
        ],

        


    ],
];

$this->setColumns($columns);
*/

namespace Com\DataGrid;

use Zend, Com;
use Zend\Escaper;
use Zend\Paginator\Adapter\NullFill;
use Zend\Paginator\Adapter\AdapterInterface;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\Adapter\Adapter;
use Zend\EventManager\EventManager;
use Zend\EventManager\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventManagerAwareInterface;
use Com\ContainerAwareInterface;
use Interop\Container\ContainerInterface;

class TinyGrid implements EventManagerAwareInterface, ContainerAwareInterface
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
    protected $_tableAttr = array(
        'class' => 'table table-bordered table-striped table-hover' #
        ,'cellspacing' => '0'
        ,'width' => '100%'
        ,'border' => '0'
    );

    /**
     * @var array
     */
    protected $_paginatorConfig = array(

        # attributes used in the paginator
        'container' => array('class' => 'pagination-container pull-right'),
        'paginator' => array('class' => 'pagination'),
        'li' => array('class' => ''),
        'a' => array(),

        # classes
        'current_class' => 'active',
        'disabled_class' => 'disabled',

        # the text used in the title attribute of the links
        'titles' => array(
            'first' => 'First Page',
            'previous' => 'Previous Page',

            'next' => 'Next Page',
            'last' => 'Last Page',
        ),

        # the labels used in the links 
        'labels' => array(
            'first' => '<span><<</span>',
            'previous' => '<span><</span>',

            'next' => '<span>></span>',
            'last' => '<span>>></span>',          
        ),

        # templates used in the info
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
    protected  $_showPaginator = true;

    /**
     * @var string
     */
    protected  $_paginatorPosition = 'bottom'; // top/bottom/both/none

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
     * @var array
     */
    protected $_query;

    /**
     * @var array
     */
    protected $_params;

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
    #protected $_sortBy = array();

    /**
     * @var Zend\Paginator\Paginator
     */
    protected $_paginator;

    /**
     * @var Select|Iterator|array
     */
    protected $_source = null;

    /**
     * @var Zend\Db\Adapter\Adapter
     */
    protected $_dbAdapter = null;

    /**
     * @var EventManagerInterface
     */
    protected $_eventManager;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var bool
     */
    protected $_built = false;

    /**
     * @var Zend\Escaper\Escaper
     */
    protected $_escaper = null;

    /**
     * @var array
     */
    protected $_default_sort;

   
    
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
     * Translate a message using the given text domain and locale
     *
     * @param string $message
     * @param string $textDomain
     * @param string $locale
     * @return string
     */
    function _($message, $textDomain = 'default', $locale = null)
    {
        $sm = $this->getContainer();

        if($sm->has('translator'))
        {
            $message = $sm->get('translator')->translate($message, $textDomain, $locale);
        }

        return $message;
    }



    /**
     * Set instance of Escaper
     *
     * @param  Escaper\Escaper $escaper
     */
    public function setEscaper(Escaper\Escaper $escaper)
    {
        $this->_escaper  = $escaper;
        return $this;
    }

    /**
     * Get instance of Escaper
     *
     * @return null|Escaper\Escaper
     */
    public function getEscaper()
    {
        if(null === $this->_escaper)
        {
            $this->setEscaper(new Escaper\Escaper('utf-8'));
        }

        return $this->_escaper;
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

        if(isset($config['li']) && is_array($config['li']))
        {
            $this->_paginatorConfig['li'] = $config['li'];
        }

        if(isset($config['a']) && is_array($config['a']))
        {
            $this->_paginatorConfig['a'] = $config['a'];
        }

        if(isset($config['current_class']))
        {
            $this->_paginatorConfig['current_class'] = $config['current_class'];
        }

        if(isset($config['disabled_class']))
        {
            $this->_paginatorConfig['disabled_class'] = $config['disabled_class'];
        }

        if(isset($config['titles']) && is_array($config['titles']))
        {
            $this->_paginatorConfig['titles']['first'] = isset($config['titles']['first']) ? $config['titles']['first'] : null;
            $this->_paginatorConfig['titles']['previous'] = isset($config['titles']['previous']) ? $config['titles']['previous'] : null;
            $this->_paginatorConfig['titles']['next'] = isset($config['titles']['next']) ? $config['titles']['next'] : null;
            $this->_paginatorConfig['titles']['last'] = isset($config['titles']['last']) ? $config['titles']['last'] : null;
        }

        if(isset($config['labels']) && is_array($config['labels']))
        {
            $this->_paginatorConfig['labels']['first'] = isset($config['labels']['first']) ? $config['labels']['first'] : null;
            $this->_paginatorConfig['labels']['previous'] = isset($config['labels']['previous']) ? $config['labels']['previous'] : null;
            $this->_paginatorConfig['labels']['next'] = isset($config['labels']['next']) ? $config['labels']['next'] : null;
            $this->_paginatorConfig['labels']['last'] = isset($config['labels']['last']) ? $config['labels']['last'] : null;
        }

        if(isset($config['templates']) && is_array($config['templates']))
        {
            $this->_paginatorConfig['templates']['paginator_info'] = isset($config['templates']['paginator_info']) ? $config['templates']['paginator_info'] :  null;
        }

        return $this;
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
     * @param string $key
     * @param string $value
     */
    function setQueryParam($key, $value)
    {
        $this->_query[$key] = $value;
        return $this;
    }


    /**
     * @param string $key
     * @param string $def
     * @return mixed
     */
    function getQueryParam($key, $def = null)
    {
        $r = $def;
        if(isset($this->_query[$key]))
        {
            $r = $this->_query[$key];
        }

        return $r;
    }


    /**
     * @return array
     */
    function getQueryParams()
    {
        return $this->_query;
    }



    /**
     * @param array $params
     */
    function setParams(array $params)
    {
        $this->_params = $params;
        return $this;
    }

    /**
     * @param string $key
     * @param string $value
     */
    function addParam($key, $value)
    {
        return $this->setParam($key, $value);
    }

    /**
     * @param string $key
     * @param string $value
     */
    function setParam($key, $value)
    {
        $this->_params[$key] = $value;
        return $this;
    }

    /**
     * @param string $key
     * @param string $dev
     * @return mixed
     */
    function getParam($key, $def = null)
    {
        $r = $def;
        if(isset($this->_params[$key]))
        {
            $r = $this->_params[$key];
        }

        return $r;
    }


    /**
     * @return array
     */
    function getParams()
    {
        return $this->_params;
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
        $tmp = $this->$var;
        if(isset($tmp[$key]))
        {
            return $tmp[$key];
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
        return $this;
    }


    /**
     * @return array
     */
    function getColumns()
    {
        return $this->_columns;
    }


    /**
     * @param bool $var
     */
    function setShowPaginator($val)
    {
        $this->_showPaginator = (bool)$val;
    }


    /**
     * @return bool
     */
    function getShowPaginator()
    {
        return $this->_showPaginator;
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
    function setPaginatorPosition($val)
    {
        $this->_paginatorPosition = $val;
        return $this;
    }


    /**
     * @return string
     */
    function getPaginatorPosition()
    {
        return $this->_paginatorPosition;
    }


    /**
     * @param Select|Iterator|array $source
     * @param Zend\Db\Adapter\Adapter $dbAdapter
     * @return TinyGrid
     */
    function setSource($source, $dbAdapter = null)
    {
        $this->_paginator = null;
        $this->_built = false;

        if($source instanceof Select 
            || is_array($source)
            || $source instanceof \Iterator
            || $source instanceof Com\Db\AbstractDb)
        {

            if($source instanceof Zend\Db\Sql\Select)
            {
                if(!$dbAdapter instanceof Zend\Db\Adapter\Adapter)
                {
                    throw new \Exception('Missing $dbAdapter parameter.');
                }
                else
                {
                    $this->_dbAdapter = $dbAdapter;
                }
            }
            elseif($source instanceof Com\Db\AbstractDb)
            {
                $this->_dbAdapter = $source->getDbAdapter();
                $source = $source->getSql()->select();
            }
            elseif($source instanceof Zend\Db\TableGateway\AbstractTableGateway)
            {
                $this->_dbAdapter = $source->getAdapter();
                $source = $source->getSql()->select();
            }

            $this->_source = $source;

            $this->_setSource($source);
        }
        else
        {
            throw new \Exception('$source parameter must be a valid instance of: Select, Iterator or array');
        }

        return $this;
    }


    private function _setSource($source)
    {
        $this->_source = $source;
    }


    /**
     * @return Select|Iterator|array|null
     */
    function getSource()
    {
        return $this->_source;
    }


    /**
     * @return Zend\Db\Adapter\Adapter
     */
    function getDbAdapter()
    {
        return $this->_dbAdapter;
    }



    /**
     * @return Zend\Paginator\Paginator
     */
    function getPaginator()
    {
        if(!$this->_paginator instanceof Paginator)
        {
            $source = $this->getSource();
            if($source instanceof Zend\Db\Sql\Select)
            {
                $adapter = new Zend\Paginator\Adapter\DbSelect($source, $this->getDbAdapter());
            }
            elseif($source instanceof \Iterator)
            {
                $adapter = new Zend\Paginator\Adapter\Iterator($source);
            }
            elseif(is_array($source))
            {
                $adapter = new Zend\Paginator\Adapter\ArrayAdapter($source);
            }

            elseif($source instanceof Com\Db\AbstractDb)
            {
                $select = $source->getSql()->select();
                $adapter = new Zend\Paginator\Adapter\DbSelect($select, $this->getDbAdapter());
            }
            elseif($source instanceof Zend\Db\TableGateway\AbstractTableGateway)
            {
                $adapter = new Zend\Paginator\Adapter\DbTableGateway($source);
            }
            else
            {
                $adapter = new NullFill();
            }

            $this->_paginator = new Paginator($adapter);
        }
        
        return $this->_paginator;
    }



    /*
     *
     */
    function build()
    {
        if(!$this->_built)
        {
            $qParams = $this->getQueryParams();
            $gridName = $this->getGridName();

            $this->_buildColumns();
            $this->_buildDatasource();

            {
                $source = $this->getSource();
                $source = $this->_applySort($source);
                $source = $this->_applyFilter($source);

                $this->_setSource($source);
            }

            $paginator = $this->getPaginator();

            #
            $pageKey = "{$gridName}{$this->getPageVarName()}";
            $pageNumber = isset($qParams[$pageKey]) ? $qParams[$pageKey] : 1;
            $paginator->setCurrentPageNumber($pageNumber);

            #
            $pageKey = "{$gridName}{$this->getLimitVarName()}";

            $limitNumber = isset($qParams[$pageKey]) ? $qParams[$pageKey] : $this->getDefaultLimit();
            $paginator->setItemCountPerPage($limitNumber);

            $this->_built = true;
        }
        
        return $this;
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
        if(!$this->_built)
        {
            $this->build();
        }

        $paginator = $this->rederPaginator();
        $position = $this->getPaginatorPosition();

        $html = '';
        $html .= sprintf('<div%s>', $this->_attrToStr($this->_mainContainerAttr));

        $html .= (('top' == $position) || ('both' == $position)) ? $paginator : '';

        $html .= sprintf('<div%s>', $this->_attrToStr($this->_tableContainerAttr));

        $html .= sprintf('<table%s>', $this->_attrToStr($this->_tableAttr));
        $html .= $this->_renderHeader();
        $html .= $this->_renderHeaderFilter();
        $html .= $this->_renderRows();
        $html .= '</table>';

        $html .= '</div>';

        $html .= (('bottom' == $position) || ('both' == $position)) ? $paginator : '';

        $html .= '</div>';


        #
        $gridName = $this->getGridName();
        $qParams = $this->getQueryParams();

        $sParams = json_encode($qParams);

        #
        $gridSearchClass = $this->_getGridSearchClass();

        $html .= '<script type="text/javascript">';

        $html .= "
        function search_{$gridName}(val, name) {
            var s_params = $sParams;
            s_params[name] = val;

            var params = '';
            for(i in s_params)
            {
                var s = s_params[i];

                if('' == s)
                {
                    continue;
                }

                params += i + '=' + s + '&';
            }

            if('&' == params.slice(-1))
            {
                params = params.substring(0, params.length - 1);
            }

            var currPath = '{$this->_currentPath}';

            if(params != '')
            {
                currPath += '?' + params;
            }

            location.href = currPath;
        };

        $('.{$gridSearchClass}').on('keyup', function(e){
            if(e.keyCode == 13)
            {
                search_{$gridName}($(this).val(), $(this).attr('name'));
            };
        });

        $('select.{$gridSearchClass}').on('change', function(e){
            search_{$gridName}($(this).val(), $(this).attr('name'));
        });
        ";

        $html .= '</script>';

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
     * Generate html for the grid header
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


    /**
     * @return string
     */
    protected function _getGridSearchClass()
    {
        $gridName = $this->getGridName();
        $gridSearchClass = 'grid-search';
        if(!empty($gridName))
        {
            $gridSearchClass .= "-{$gridName}";
        }

        return $gridSearchClass;
    }


    protected function _getSearchTerms()
    {
        $gridName = $this->getGridName();
        $qParams = $this->getQueryParams();

        #
        $fieldNamePrefix = 'filter_';
        if(!empty($gridName))
        {
            $fieldNamePrefix = "{$gridName}_";
        }


        #
        $config = $this->_getSearchConfig();

        foreach($config as $key => $item)
        {
            $theSearch = '';
            $fieldName = $fieldNamePrefix . $key;
            if(isset($qParams[$fieldName]))
            {
                $theSearch = $qParams[$fieldName];
            }

            $config[$key]['search'] = $theSearch;

            
        }

        return $config;
    }


    protected function _getSearchConfig()
    {
        $forSearch = array();

        $columns = $this->getColumns();
        foreach($columns as $name => $item)
        {
            if(isset($item['header']))
            {
                $header = $item['header'];
                if(isset($header['filter']) && (true == $header['filter']))
                {
                    $columSearch = isset($header['filter_column']) ? $header['filter_column'] : array($name);

                    $forSearch[$name] = array(
                        'columns' => $columSearch
                    );

                    if(isset($header['filter_values']) && is_array($header['filter_values']))
                    {
                        $forSearch[$name]['filter_values'] = $header['filter_values'];
                    }

                    if(isset($header['filter_values']) && is_array($header['filter_values']))
                    {
                        $forSearch[$name]['filter_type'] = '=';
                    }
                    elseif(isset($header['filter_type']))
                    {
                        $flag = ('=' == $header['filter_type']);
                        $flag = $flag || ('>' == $header['filter_type']);
                        $flag = $flag || ('<' == $header['filter_type']);
                        $flag = $flag || ('>=' == $header['filter_type']);
                        $flag = $flag || ('<=' == $header['filter_type']);

                        if($flag)
                        {
                            $forSearch[$name]['filter_type'] = $header['filter_type'];
                        }
                    }
                }
            }
        }

        return $forSearch;
    }



    /**
     * Generate html for the grid header filter
     * @return  string
     */
    protected  function _renderHeaderFilter()
    {
        if(!$this->getShowHeader())
        {
            return '';
        }

        $columns = $this->getColumns();
        $forSearch = $this->_getSearchConfig();

        if(!count($forSearch))
        {
            return '';
        }

        #
        $headerAttr = $this->_headerAttr;
        if(isset($headerAttr['class']))
        {
            $headerAttr['class'] = ' filters';
        }
        else
        {
            $headerAttr['class'] = 'filters';
        }

        $html = '';
        $html .= '<thead>';
        $html .= sprintf('<tr%s>', $this->_attrToStr($headerAttr));
        $counter = 0;

        foreach($columns as $field => $null)
        {
            $html .= '<th>';
            if(isset($forSearch[$field]))
            {
                $html .= $this->_buildFilter($field, $forSearch[$field]);
            }
            $html .= '</th>';

            $counter++;
        }

        $html .= '</tr>';
        $html .= '</thead>';

        return $html;
    }


    protected function _buildFilter($field, $config)
    {
        $qParams = $this->getQueryParams();

        $gridName = $this->getGridName();
        $gridSearchClass = $this->_getGridSearchClass();

        $fieldNamePrefix = 'filter_';
        if(!empty($gridName))
        {
            $fieldNamePrefix = "{$gridName}_";
        }

        $fieldName = $fieldNamePrefix . $field;

        $theSearch = '';
        if(isset($qParams[$fieldName]))
        {
            $theSearch = (string)$this->getEscaper()->escapeHtml($qParams[$fieldName]);
        }

        if(isset($config['filter_values']))
        {
            $input = '';
            $input .= sprintf('<select name="%s" id="%s" class="form-control form-control-sm input-sm %s">', $fieldName, $fieldName, $gridSearchClass);

            $input .= '<option value="">--</option>';
            foreach($config['filter_values'] as $key => $value)
            {
                $selected = '';
                if((string)$key === $theSearch)
                {
                    $selected = 'selected="selected"';
                }

                $input .= sprintf('<option value="%s" %s>%s</option>', $key, $selected, $value);
            }
            $input .= '</select>';

            return $input;
        }
        else
        {
            return sprintf('<input type="text" value="%s" name="%s" id="%s" class="form-control %s form-control-sm input-sm">', $theSearch, $fieldName, $fieldName, $gridSearchClass);
        }
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
            $row = $event->getParam('row');

            #
            $html .= sprintf('<tr%s>', $this->_attrToStr($attributes));

            foreach ($this->_columns as $field => $config)
            {
                $cellConfig = isset($config['cell']) ? (array)$config['cell'] : array();
                
                $html .= $this->_renderRow($field, $cellConfig, $row);
            }

            $html .= '</tr>';
        }

        return $html;
    }

    
    /**
     * @return  string
     */    
    protected function _renderRow($field, $config, $row)
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

                $format_to = isset($config['date_format_to']) ? $config['date_format_to'] : null;
                $format_from = isset($config['date_format_from']) ? $config['date_format_from'] : null;
                $def = isset($config['empty_date']) ? $config['empty_date'] : '-';

                if($value)
                {
                    $value = $this->_getDateFormated($value, $format_from, $format_to, $def);
                }
                else
                {
                    $value = $def;
                }

            break;
            
            case 'relative_date':

                $value = $this->_getRelativeDate($value);

            break;

            case 'action':
                $value = array();

                if(isset($config['data']) && is_callable($config['data']))
                {
                    $value = call_user_func($config['data'], $row, $config);
                }

                $theMenu = '';
                if(is_array($value) && count($value))
                {
                    $btnType = isset($config['btn_type']) ? $config['btn_type'] : 'btn-default';
                    $btnSize = isset($config['btn_size']) ? $config['btn_size'] : 'btn-xs';

                    $propsToString = function(array $props) {

                        $arr = array();

                        foreach($props as $key => $value)
                        {
                            $arr[] = sprintf('%s="%s"', $key, $value);
                        }

                        return implode(' ', $arr);
                    };

                    $theMenu = '<div class="text-center">';
                    $theMenu .= '<div class="btn-group">';
                    $counter = 0;
                    foreach($value as $item)
                    {
                        $href = isset($item['href']) ? $item['href'] : '';
                        $label = isset($item['label']) ? $item['label'] : '';

                        $attributes = array();
                        if(isset($item['attributes']) && is_array($item['attributes']))
                        {
                            $attributes = $item['attributes'];
                        }

                        if(isset($attributes['href']))
                            unseet($attributes['href']);

                        $attributes['href'] = $href;


                        if(0 == $counter)
                        {
                            $class = isset($attributes['class']) ? $attributes['class'] : '';

                            $props = array(
                                'class' => "btn $btnType $btnSize $class",
                            );

                            $attributes = array_merge($attributes, $props);
                            $theMenu .= sprintf('<a %s>%s</a>', $propsToString($attributes), $label);
                            $theMenu .= sprintf('<button type="button" class="btn dropdown-toggle %s" data-toggle="dropdown"><span class="caret"></span></button><ul class="dropdown-menu">', "$btnType $btnSize");
                        }
                        else
                        {
                            $theMenu .= sprintf('<li><a %s>%s</a></li>', $propsToString($attributes), $label);
                        }

                        $counter++;
                    }

                    $theMenu .= '</ul></div></div>';
                }

                $value = $theMenu;

                $values = '
                <div class="btn-group">

                  <a href="" class="btn btn-xs btn-default">Action</a>

                  <button type="button" class="btn btn-xs btn-default dropdown-toggle" data-toggle="dropdown">
                    <span class="caret"></span>
                  </button>

                  <ul class="dropdown-menu">
                    <li><a href="#">Action</a></li>
                    <li><a href="#">Another action</a></li>
                    <li><a href="#">Something else here</a></li>
                    <li role="separator" class="divider"></li>
                    <li><a href="#">Separated link</a></li>
                  </ul>
                </div>
                ';

            break;
            
            case 'money': 

                $field_money_sign = isset($config['sign']) ? $config['sign'] : '$';
                $field_decimal_places = isset($config['decimal_places']) ? $config['decimal_places'] : 2;
                $field_dec_separator = isset($config['decimal_separator']) ? $config['decimal_separator'] : '.';
                $field_thousands_separator = isset($config['thousands_separator']) ? $config['thousands_separator'] : ',';

                $value = $field_money_sign . number_format($value, $field_decimal_places, $field_dec_separator, $field_thousands_separator);

            break;

            case 'number': 

                $value = sprintf('<div style="text-align:right">%s</div>', $value);

            break;

            case 'amount':

                $field_dec_separator = isset($config['decimal_separator']) ? $config['decimal_separator'] : '.';
                $field_thousands_separator = isset($config['thousands_separator']) ? $config['thousands_separator'] : ',';

                $value = number_format($value, 0, $field_dec_separator, $field_thousands_separator);
                $value = sprintf('<div style="text-align:right">%s</div>', $value);

            break;
            
            case 'password':
            case 'mask':

                $field_symbol = isset($config['symbol']) ? $config['symbol'] : '*';
                $value = str_repeat($field_symbol, strlen($value));

            break;
        }

        if(isset($config['callback']) && is_callable($config['callback']))
        {
            $value = call_user_func($config['callback'], $value, $row, $field, $config);

            if(isset($config['menu']) && is_callable($config['menu']))
            {
                $value .= call_user_func($config['menu'], $row, $config);
            }

            $html .= $value;
        }
        else
        {
            if(isset($config['menu']) && is_callable($config['menu']))
            {
                $value .= call_user_func($config['menu'], $row, $config);
            }

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

        $currentClass = $config['current_class'];
        $disabledClass = $config['disabled_class'];

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
        if($this->getShowPaginator())
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
    protected function _getDateFormated($date_str, $format_from = null, $format_to = null, $defaultOnEmpty = null)
    {
        if(empty($date_str)) 
        {
            return $defaultOnEmpty;
        }

        if(empty($format_from))
        {
            $format_from = 'y-MM-d HH:mm:ss';
        }

        if(empty($format_to))
        {
            $format_to = 'y-MM-d HH:mm:ss';
        }

        $date = new Com\Zend\Date($date_str, $format_from);
        return $date->get($format_to);
    }


    protected function _buildDatasource()
    {
        return $this;
    }


    protected function _buildColumns()
    {
        return $this;
    }


    /**
     * @return array
     */
    protected function _getSortColumn()
    {
        $qParams = $this->getQueryParams();
        $gridName = $this->getGridName();
        $sortVarName = $this->getSortVarName();

        $key = "{$gridName}{$sortVarName}";
        $sortColumn = array();

        if(isset($qParams[$key]))
        {
            $column = $qParams[$key];
            $cols = $this->getColumns();
            if(isset($cols[$column]))
            {
                $col = $cols[$column];
                if(isset($col['header']) && isset($col['header']['sort']) && (true == $col['header']['sort']))
                {
                    if(isset($col['header']['sort_column']))
                    {
                        if(is_array($col['header']['sort_column']))
                        {
                            $sortColumn = $col['header']['sort_column'];
                        }
                        else
                        {
                            $sortColumn = array($col['header']['sort_column']);
                        }
                    }
                    else
                    {
                        $sortColumn = array($column);
                    }
                }
            }
        }
        else
        {
            $columns = $this->getColumns();
            foreach($columns as $column => $item)
            {
                if(isset($item['header']))
                {
                    if(isset($item['header']['sort_default']))
                    {
                        $sortColumn = array($column);
                        break;
                    }
                }
            }
        }

        return $sortColumn;
    }

    protected function _getSortOrder()
    {
        $qParams = $this->getQueryParams();
        
        $gridName = $this->getGridName();
        $orderVarName = $this->getOrderVarName();

        $key = "{$gridName}{$orderVarName}";
        $sortOrder = 'asc';

        if(isset($qParams[$key]))
        {
            $sortOrder = strtolower($qParams[$key]);
            if(($sortOrder != 'desc') && ($sortOrder != 'asc'))
            {
                $sortOrder = 'asc';
            }
        }
        else
        {
            $columns = $this->getColumns();
            foreach($columns as $column => $item)
            {
                if(isset($item['header']))
                {
                    if(isset($item['header']['sort_default']))
                    {
                        $sortOrder = $item['header']['sort_default'];
                        break;
                    }
                }
            }
        }

        return $sortOrder;
    }


    protected function _applySort($source)
    {
        $sortOrder = $this->_getSortOrder();
        $sortColumn = $this->_getSortColumn();

        if($source instanceof Zend\Db\Sql\Select)
        {
            $source = $this->_applySortSelect($source, $sortOrder, $sortColumn);
        }
        elseif($source instanceof \Iterator)
        {
            $source = $this->_applySortIterator($source, $sortOrder, $sortColumn);
        }
        elseif(is_array($source))
        {
            $source = $this->_applySortArray($source, $sortOrder, $sortColumn);
        }

        return $source;
    }


    protected function _applySortArray($source, $sortOrder, $sortColumn)
    {
        $array_sort = function($array, $cols)
        {
            $colarr = array();
            foreach($cols as $col => $order)
            {
                $colarr[$col] = array();
                foreach($array as $k => $row)
                {
                    $colarr[$col]['_' . $k] = strtolower($row[$col]);
                }
            }

            $eval = 'array_multisort(';
            foreach($cols as $col => $order)
            {
                $eval .= '$colarr[\''.$col.'\'],'.$order.',';
            }

            $eval = substr($eval,0,-1).');';
            eval($eval);

            $ret = array();
            foreach($colarr as $col => $arr)
            {
                foreach($arr as $k => $v)
                {
                    $k = substr($k,1);
                    if (!isset($ret[$k])) $ret[$k] = $array[$k];
                    $ret[$k][$col] = $array[$k][$col];
                }
            }

            return $ret;
        };

        #
        $sort = array();
        foreach($sortColumn as $column)
        {
            $sort[$column] = ('desc' == $sortOrder) ? SORT_DESC : SORT_ASC;
        }

        if(count($sort))
        {
            $source = $array_sort($source, $sort);
        }
        
        return $source;
    }


    protected function _applySortIterator($source, $sortOrder, $sortColumn)
    {
        throw new \Exception('Sorting Iterator is not implemented yet');
        return $source;
    }


    protected function _applySortSelect($source, $sortOrder, $sortColumn)
    {
        foreach($sortColumn as $column)
        {
            $source->order("$column $sortOrder");
        }
        
        return $source;
    }
    

    protected function _applyFilter($source)
    {
        $config = $this->_getSearchTerms();

        if($source instanceof Zend\Db\Sql\Select)
        {
            $source = $this->_applyFilterSelect($source, $config);
        }
        elseif($source instanceof \Iterator)
        {
            $source = $this->_applyFilterIterator($source, $config);
        }
        elseif(is_array($source))
        {
            $source = $this->_applyFilterArray($source, $config);
        }

        return $source;
    }


    protected function _applyFilterIterator($source, $config)
    {
        throw new \Exception('Filter Iterator is not implemented yet');
        return $source;
    }


    protected function _applyFilterArray($source, $config)
    {
        throw new \Exception('Filter array is not implemented yet');
        return $source;
    }


    protected function _applyFilterSelect($source, $config)
    {
        $where = $source->getRawState('where');
        if(!$where)
        {
            $where = new Where();
        }
        
        foreach($config as $item)
        {
            $search = (string)$item['search'];
            $filterType = isset($item['filter_type']) ? $item['filter_type'] : null;

            $applyFilterType = false;
            if($filterType)
            {
                $applyFilterType = true;
            }

            if($search !== '')
            {
                foreach($item['columns'] as $column)
                {
                    $s = substr($search, 0, 1);
                    $s2 = substr($search, 0, 2);

                    if(('=' == $filterType) || ('=' == $s))
                    {
                        if(!$applyFilterType)
                            $search = substr($search, 1);

                        $where->equalTo($column, $search);
                    }
                    elseif(('>=' == $filterType) || ('>=' == $s2))
                    {
                        if(!$applyFilterType)
                            $search = substr($search, 2);

                        $where->greaterThanOrEqualTo($column, $search);
                    }
                    elseif(('<=' == $filterType) || ('<=' == $s2))
                    {
                        if(!$applyFilterType)
                            $search = substr($search, 2);

                        $where->lessThanOrEqualTo($column, $search);
                    }
                    elseif(('>' == $filterType) || ('>' == $s))
                    {
                        if(!$applyFilterType)
                            $search = substr($search, 1);

                        $where->greaterThan($column, $search);
                    }
                    elseif(('<' == $filterType) || ('<' == $s))
                    {
                        if(!$applyFilterType)
                            $search = substr($search, 1);

                        $where->lessThan($column, $search);
                    }
                    else
                    {
                        $where->like($column, "%$search%");
                    }
                }
            }
        }

        $source->where($where);

        return $source;
    }
}