<?php
namespace Com\Html;
use Com;

class ActionMenu
{
    /**
     * @var array
     */
    protected $buttons = array();
 
 
    static function getInstance()
    {
        return new self();
    }
    
    function addButton(array $properties, $text = null)
    {
        $url = isset($properties['href']) ? $properties['href'] : null;
        $link = Com\Html\Writer::link($url, $text, $properties);
        
        $this->buttons[] = $link;
        
        return $this;
    }
    
    
    function addEdit($url)
    {
        $prop['href'] = $url;
        $prop['class'] = 'btn btn-xs btn-default btn-edit';
        $prop['title'] = 'Edit';
        
        $this->addButton($prop, '<i class="fa fa-pencil"></i>');
        return $this;
    }
    
    
    function addDelete($url)
    {
        $prop['href'] = $url;
        $prop['class'] = 'btn btn-xs btn-default btn-delete';
        $prop['title'] = 'Delete';
        $prop['onclick'] = 'return confirm("Please confirm that you want to remove the selected item");';
        
        $this->addButton($prop, '<i class="fa fa-trash"></i>');
        return $this;
    }
    
    
    function toString()
    {
        $buttons = implode(' ', $this->buttons);
        $span = Com\Html\Writer::span($buttons, 'action-menu');
        
        return $span;
    }
}