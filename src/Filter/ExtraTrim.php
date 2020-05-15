<?php

namespace Com\Filter;

use Laminas\Filter\AbstractFilter;

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
