<?php

/**
 * @author <yoterri@ideasti.com>
 * @copyright 2014
 */
namespace Com;

class Options
{

    public static function setOptions($object, $options)
    {
        if(! is_object($object))
        {
            return;
        }
        
        if(is_object($options))
        {
            if(method_exists($options, 'toArray()'))
                $options = $options->toArray();
            elseif(method_exists($options, 'getArrayCopy()'))
                $options = $options->getArrayCopy();
        }
        
        if(is_array($options))
        {
            foreach($options as $key => $value)
            {
                $method = 'set' . self::_normalizeKey($key);
                if(method_exists($object, $method))
                {
                    $paramFunc = array();
                    $paramFunc[] = $object;
                    $paramFunc[] = $method;
                    
                    if(is_array($value))
                        call_user_func_array($paramFunc, $value);
                    else
                        call_user_func($paramFunc, $value);
                }
            }
        }
    }

    protected static function _normalizeKey($key)
    {
        $option = str_replace('_', ' ', strtolower($key));
        $option = str_replace(' ', '', ucwords($option));
        return $option;
    }
}