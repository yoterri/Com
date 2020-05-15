<?php

namespace Com\Filter;

use Laminas\Filter\Boolean as zBoolean;

class Boolean extends zBoolean
{

    /**
     *
     * @param  string $value
     * @return float|mixed
     */
    public function filter($value)
    {
        $r = 0;
        
        if($value)
        {
            $r = 1;
        }

        return $r;
    }
}
