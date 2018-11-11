<?php

namespace Com\Filter;

use Zend\Filter\AbstractFilter;

class ToFloat extends AbstractFilter
{


	protected $decimalPlaces = 2;


    /**
     *
     * @param  string $value
     * @return float|mixed
     */
    public function filter($value)
    {
        if(!is_scalar($value))
        {
            return $value;
        }

        $value = (string)$value;

        $d = $this->decimalPlaces;

        $padded = sprintf("%0.{$d}f", $value);

        return $padded;
    }


    function setDecimalPlaces($num)
    {
    	$this->decimalPlaces = (int)abs($num);
    	return $this;
    }
}
