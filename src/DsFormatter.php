<?php

namespace Com;
use Zend;
use Com\LazyLoadInterface;

class DsFormatter implements LazyLoadInterface
{

     /**
     *
     * @param array
     */
    function __construct($rowset = null)
    {
        if(! is_null($rowset))
        {
            $this->setDataSource($rowset);
        }
    }
    
    
   
    function setDataSource($rowset)
    {
        if(is_object($rowset))
        {
            if(!$rowset instanceof \Iterator)
            {
                if(method_exists($rowset, 'toArray'))
                {
                    
                    $rowset = $rowset->toArray();
                }
                elseif(method_exists($rowset, 'getArrayCopy'))
                {
                    
                    $rowset = $rowset->getArrayCopy();
                }
                else
                {
                    throw new \Exception("Datasource Not Supported");
                }
            }
        }
        elseif(!is_array($rowset))
        {
            $rowset = array();
        }
        
        $this->_data = $rowset;

        return $this;
    }
    
    
    function toJson(array $fields = array())
    {
        $r = $this->toArray($fields);
        return Zend\Json\Json::encode($r);
    }
    
    
    
    /**
     *
     * @param array $fields
     * @return array
     * 
     * @example example 1
     * $fields = array('f1', 'f2');
     * $formatter->toArray($fields);
     *
     * example 2
     * $fields = array('custom_column_name' => 'column_name', 'f2' => 'field 2');
     * $formatter->toArray($fields);
     * 
     * example 3
     * $fields = array('custom_column_name' => 'column_name', 'title' => function($row){},);
     * $formatter->toArray($fields);
     */
    function toArray(array $fields = array())
    {
        $r = array();
        $data = $this->_data;

        if(count($fields) > 0)
        {
            $c = 0;
            foreach($data as $row)
            {
                if(!is_array($row))
                {
                    $row = $row->toArray();
                }

                foreach($fields as $key => $field)
                {
                    # no special format, only providing the key of the value
                    if(is_int($key))
                    {
                        $value = '';
                        if(isset($row[$field]))
                        {
                            $value = $row[$field];
                        }
                        
                        $r[$c][$field] = $value;
                    }
                    else
                    {
                        $value = '';
                        if(is_callable($field))
                        {
                            $value = call_user_func($field, $row, $key, $c);
                        }
                        else
                        {
                            if(isset($row[$field]))
                            {
                                $value = $row[$field];
                            }
                            else
                            {
                                $value = $field;
                            }
                        }
                        
                        $r[$c][$key] = $value;
                    }
                }
                
                $c ++;
            }
        }
        else
        {
        	if(method_exists($data, 'toArray'))
            {
                
                $r = $data->toArray();
            }
            elseif(method_exists($data, 'getArrayCopy'))
            {
                
                $r = $data->getArrayCopy();
            }
            else
            {
            	$r = $data;
            }
        }
        
        return $r;
    }
    
    /*
    protected function _arrayFormatting($array, $row)
    {
        $vars = array();
        
        foreach($array[1] as $key => $value)
        {
            if(is_array($row))
            {
                $vars[$key] = $row[$value];
            }
            else
            {
                $vars[$key] = $row->$value;
            }
        }
        
        return $this->_replace($array[0], $vars);
    }


    protected function _replace($str, array $vars = array(), $chrStart = '', $chrEnd = '')
    {
        if(! empty($chrStart) || ! empty($chrEnd))
        {
            foreach($vars as $key => $val)
            {
                $key = $chrStart . $key . $chrEnd;
                $vars[$key] = $val;
            }
        }
        
        return strtr($str, $vars);
    }
    */
}