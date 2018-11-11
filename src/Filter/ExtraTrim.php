<?php

namespace Com\Filter;

use Zend\Filter\AbstractFilter;

class ExtraTrim extends AbstractFilter
{
    
    public function filter($value)
    {
        if(!is_string($value))
        {
            return $value;
        }

        $value = (string)$value;
        return trim(preg_replace('/\s+/', ' ', trim($value)));
    }
}
