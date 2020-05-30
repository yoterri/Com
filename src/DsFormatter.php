<?php

namespace Com;
use Laminas;
use Com\Interfaces\LazyLoadInterface;

class DsFormatter implements LazyLoadInterface
{
    protected $_data;
    protected $excludeFields = [];

     /**
     *
     * @param array
     */
    function __construct($rowset = null, array $excludeFields = array())
    {
        if(! is_null($rowset))
        {
            $this->setDataSource($rowset);
        }

        $this->setExcludeFields($excludeFields);
    }


    /**
     * Undocumented function
     *
     * @param array $excludeFields
     */
    function setExcludeFields($excludeFields)
    {
        $this->excludeFields = $excludeFields;
        return $this;
    }


    /**
     * Undocumented function
     * @return array
     */
    function getExcludeFields()
    {
        return $this->excludeFields;
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
        return Laminas\Json\Json::encode($r);
    }
    
    
    
    /**
     *
     * @param array $fields
     * @param string $type - exclusive|inclusive
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
    function toArray(array $fields = array(), $type = 'exclusive')
    {
        $r = array();
        $data = $this->_data;

        if(count($fields) > 0)
        {
            $c = 0;
            $method = null;

            foreach($data as $row)
            {
                if(!is_array($row))
                {
                    if(is_null($method))
                    {
                        if(method_exists($row, 'toArray'))
                        {
                            $method = 'toArray';
                        }
                        elseif(method_exists($row, 'getArrayCopy'))
                        {
                            $method = 'getArrayCopy';
                        }
                        else 
                        {
                            $method = false;
                        }
                    }

                    if($method)
                    {
                        $row = $row->$method();
                    }
                    else
                    {
                        $row = [];
                    }
                }

                if($this->excludeFields)
                {
                    foreach($this->excludeFields as $field)
                    {
                        if(array_key_exists($field, $row))
                        {
                            unset($row[$field]);
                            continue;
                        }
                    }
                }

                if('exclusive' == $type)
                {
                    $r = $this->_rowFormatter($r, $fields, $row, $c);
                }
                elseif('inclusive' == $type)
                {
                    $r[$c] = $row;
                    $r = $this->_rowFormatter($r, $fields, $row, $c);
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

            foreach($r as $c => $row)
            {
                if ($this->excludeFields)
                {
                    foreach($this->excludeFields as $field)
                    {
                        if (isset($row[$field]))
                        {
                            unset($row[$field]);
                            continue;
                        }
                    }
                }

                $r[$c] = $row;
            }
        }
        
        return $r;
    }


    protected function ss()
    {

    }
    

    protected function _rowFormatter($r, $fields, $row, $c)
    {
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

        return $r;
    }


    /**
    * @param string|array $textField
    * @param string|array $valueField
    * @example
    *
    * example 1
    * $textField = array('te column is: %colname_1% xxx, other value %colname_2%', array('%colname_1%'=>'colname_1', '%colname_2%'=>'colname_2'));
    * $valueField = array('te column is: %colname_3% xxx, other value %colname_4%', array('%colname_3%'=>'colname_3', '%colname_4%'=>'colname_4'));
    * $formatter->toFormSelect($textField, $valueField);
    *
    * example 2
    * $textField = 'column_1';
    * $valueField = 'column_2';
    * $formatter->toFormSelect($textField, $valueField);
    *
    * example 3
    * $textField = array('the column is: %colname_1%, other value %colname_2%', array('%colname_1%' => function($row){return 21;}, '%colname_2%'=>'colname_2'));
    * $valueField = 'column_2';
    * $formatter->toFormSelect($textField, $valueField);
    *
    * @return array
    */
   function toFormSelect($textField, $valueField)
   {
        $r = array();
        $textIsArray = is_array($textField);
        $valueIsArray = is_array($valueField);

        foreach($this->_data as $row)
        {
            if($textIsArray)
            {
                $text = $this->_arrayFormatting($textField, $row);
            }
            else
            {
                $text = $row[$textField];
            }
                

            if($valueIsArray)
            {
                $value = $this->_arrayFormatting($valueField, $row);
            }
            else
            {
                $value = $row[$valueField];
            }

            $r[$value] = $text;
        }

        return $r;
   }


   protected function _arrayFormatting($array, $row)
   {
        $vars = array();

        foreach($array[1] as $key => $value)
        {
            if(is_callable($value)) {
                $value = call_user_func($value, $row);
            }

            $vars[$key] = isset($row[$value]) ? $row[$value] : $value;
        }

        return $this->_string_replace($array[0], $vars);
   }
   
   
    protected function _string_replace($str, array $vars=array())
    {
        return str_replace(array_keys($vars), array_values($vars), $str);
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
