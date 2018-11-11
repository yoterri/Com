<?php

namespace Com\Html;


/**
 *
 * @see tree http://stackoverflow.com/questions/4196157/create-array-tree-from-array-list
 * @see dropdown http://stackoverflow.com/questions/14613546/create-nested-list-from-php-array-for-dropdown-select-field
 *
 * @author yoterri
 *
 */
class NestedSelectOptions
{

    /**
     *
     * @var array
     */
    protected $data;


    /**
     *
     * @param array $data
     */
    function __construct(array $data = array())
    {
        $this->setData($data);
    }


    /**
     *
     * @param array $data
     * @return \Com\Html\NestedSelectOptions
     */
    function setData(array $data)
    {
        $items = $data;
        
        $new = array();
        foreach($items as $item)
        {
            $new[$item['parent_id']][$item['id']] = $item;
        }
        
        $this->data = $new;
        
        return $this;
    }


    /**
     *
     * @return array
     */
    function getData()
    {
        return $this->data;
    }


    /**
     *
     * @param array $list
     * @param array $parent
     * @return array
     */
    function toTree(array &$list = array(), array $parent = array())
    {
        if(0 == count($list))
            $list = $this->getData();
        
        if(0 == count($parent) && count($list) > 0)
            $parent = $list[0];
        
        $tree = array();
        
        foreach($parent as $item)
        {
            if(isset($list[$item['id']]))
            {
                $item['children'] = $this->toTree($list, $list[$item['id']]);
            }
            
            $tree[$item['id']] = $item;
        }
        
        return $tree;
    }


    /**
     *
     * @param array $tree
     * @param string $selected
     * @param int $r
     * @param string $parentId
     * @return string
     */
    function toHtmlOptions(array $tree = array(), $selected = null, $r = 0, $parentId = null)
    {
        static $html = '';
        
        if(0 == count($tree))
            $tree = $this->toTree();
        
        if(! is_null($selected))
        {
            if(! is_array($selected))
            {
                $selected = array(
                    $selected 
                );
            }
        }
        else
        {
            $selected = array();
        }
        
        foreach($tree as $item)
        {
            $espace = str_pad(' ', (($item['level'] - 1) * (30)), "&nbsp;");
            
            $strSelected = '';
            
            if(in_array($item['id'], $selected))
            {
                $strSelected = 'selected="selected"';
            }
            
            $slug = new \Com\Slugify();
            $text = $slug->create($item['name']);
            
            $html .= sprintf('<option data-text="%s" %s value="%d">%s%s</option>', $text, $strSelected, $item['id'], $espace, $item['name']);
            
            if($item['parent_id'] == $parentId)
            {
                $r = 0;
            }
            
            if(isset($item['children']))
            {
                $this->toHtmlOptions($item['children'], $selected, ++ $r, $item['parent_id']);
            }
        }
        
        return $html;
    }
}