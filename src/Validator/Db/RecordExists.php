<?php
namespace Com\Validator\Db;

use Laminas\Validator\Exception;

/**
 * Confirms a record exists in a table.
 */
class RecordExists extends \Laminas\Validator\Db\RecordExists
{

    protected $byPassValue = null;
    protected $hasByPassValue = false;


    /**
     * @param mixed $valule
     * @return ClosureNodeExists
     */
    function setByPassValue($value)
    {
        $this->byPassValue = $value;
        $this->hasByPassValue = true;
        return $this;
    }

    /**
     * @return mixed
     */
    function getByPassValue()
    {
        return $this->byPassValue;
    }

    /**
     * @return ClosureNodeExists
     */
    function removeByPassValue()
    {
        $this->hasByPassValue = false;
        return $this;
    }


    public function isValid($value)
    {
        if($this->hasByPassValue)
        {
            if(is_array($this->byPassValue))
            {
                $flag = in_array($value, $this->byPassValue);
                if($flag)
                {
                    return true;
                }
            }
            else
            {
                $flag = ($value == $this->byPassValue);
                if($flag)
                {
                    return true;
                }
            }
        }

        return parent::isValid($value);
    }
}
