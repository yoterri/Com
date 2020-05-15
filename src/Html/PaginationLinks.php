<?php
namespace Com\Html;
use Com, Laminas;

class PaginationLinks
{

	/**
     * @var array
     */
    protected $query;

        /**
     * @var string
     */
    protected $pageVarName = 'page';

    /**
     * @var Laminas\Paginator\Paginator
     */
    protected $paginator;

    /**
     * @var string
     */
    protected  $currentPath  = '';


    /**
     * @var bool
     */
    protected  $showPaginator = true;


	/**
     * @var array
     */
    protected $config = array(

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
     * @param Laminas\Paginator\Paginator $paginator
     * @param array $qParams
     * @param array $config
     */
    function __construct(Laminas\Paginator\Paginator $paginator, array $qParams = array(), array $config = array())
    {
    	$this->setPaginator($paginator);
    	$this->setConfig($config);
    	$this->setQueryParams($qParams);

    	#
    	$this->currentPath = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

        if(isset($_SERVER['QUERY_STRING']))
        {
            $this->currentPath = str_replace($_SERVER['QUERY_STRING'], '', $this->currentPath);
        }

        if(strpos($this->currentPath, '?') !== false)
        {
            $this->currentPath = str_replace('?', '', $this->currentPath);
        }
    }


    /**
     * @param Laminas\Paginator\Paginator $paginator
     */
    function setPaginator(Laminas\Paginator\Paginator $paginator)
    {
    	$this->paginator = $paginator;	
    	return $this;
    }


    /**
     * @return Laminas\Paginator\Paginator
     */
    function getPaginator()
    {
    	return $this->paginator;	
    }



    /**
     * @param array $config
     */
    function setConfig(array $config)
    {

        if(isset($config['container']) && is_array($config['container']))
        {
            $this->config['container'] = $config['container'];
        }

        if(isset($config['paginator']) && is_array($config['paginator']))
        {
            $this->config['paginator'] = $config['paginator'];
        }

        if(isset($config['li']) && is_array($config['li']))
        {
            $this->config['li'] = $config['li'];
        }

        if(isset($config['a']) && is_array($config['a']))
        {
            $this->config['a'] = $config['a'];
        }

        if(isset($config['current_class']))
        {
            $this->config['current_class'] = $config['current_class'];
        }

        if(isset($config['disabled_class']))
        {
            $this->config['disabled_class'] = $config['disabled_class'];
        }

        if(isset($config['titles']) && is_array($config['titles']))
        {
            $this->config['titles']['first'] = isset($config['titles']['first']) ? $config['titles']['first'] : null;
            $this->config['titles']['previous'] = isset($config['titles']['previous']) ? $config['titles']['previous'] : null;
            $this->config['titles']['next'] = isset($config['titles']['next']) ? $config['titles']['next'] : null;
            $this->config['titles']['last'] = isset($config['titles']['last']) ? $config['titles']['last'] : null;
        }

        if(isset($config['labels']) && is_array($config['labels']))
        {
            $this->config['labels']['first'] = isset($config['labels']['first']) ? $config['labels']['first'] : null;
            $this->config['labels']['previous'] = isset($config['labels']['previous']) ? $config['labels']['previous'] : null;
            $this->config['labels']['next'] = isset($config['labels']['next']) ? $config['labels']['next'] : null;
            $this->config['labels']['last'] = isset($config['labels']['last']) ? $config['labels']['last'] : null;
        }

        if(isset($config['templates']) && is_array($config['templates']))
        {
            $this->config['templates']['paginator_info'] = isset($config['templates']['paginator_info']) ? $config['templates']['paginator_info'] :  null;
        }

        return $this;
    }


    /**
     * @return array 
     */
    function getConfig()
    {
        return $this->config;
    }


    /**
     * @param array $params
     */
    function setQueryParams(array $params)
    {
        $this->query = $params;
        return $this;
    }


    /**
     * @param string $key
     * @param string $value
     */
    function setQueryParam($key, $value)
    {
        $this->query[$key] = $value;
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
        if(isset($this->query[$key]))
        {
            $r = $this->query[$key];
        }

        return $r;
    }


    /**
     * @return array
     */
    function getQueryParams()
    {
        return $this->query;
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
            
        $this->pageVarName = $val;
        return $this;
    }

    
    /**
     * @return string
     */
    function getPageVarName()
    {
        return $this->pageVarName;
    }


    /**
     * @param bool $var
     */
    function setAlwaysShow($val)
    {
        $this->showPaginator = (bool)$val;
    }


    /**
     * @return bool
     */
    function getAlwaysShow()
    {
        return $this->showPaginator;
    }


    function render()
    {
    	echo $this->toString();
    }


    /**
     * @return string
     */
    function toString()
    {
    	return $this->_renderPaginator();
    }


    /**
     * @return string
     */
    function __toString()
    {
    	return $this->_renderPaginator();
    }


    /**
     * @return string
     */
    protected function _renderPaginator()
    {
    	$paginator = $this->getPaginator();
        $pages = $paginator->getPages();

        $html = '';
        if($this->getAlwaysShow() || ($pages->pageCount > 1))
        {
            #
            $config = $this->getConfig();

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



    protected function _createLink($type, $current=false, $active=true)
    {
        $params = $this->getQueryParams();
        $pageKey = $this->getPageVarName();

        if(isset($params[$pageKey]))
        {
            unset($params[$pageKey]);
        }

        #
        $config = $this->config;
        $currentPath = $this->currentPath;

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

}