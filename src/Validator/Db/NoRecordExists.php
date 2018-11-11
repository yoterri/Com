<?php

namespace Com\Validator\Db;

use Zend\Validator\Db\NoRecordExists as zNoRecordExists;

class NoRecordExists extends zNoRecordExists
{

    public function isValid($value, $context = array())
    {
        $exclude = $this->getExclude();
        if(is_array($exclude))
        {
            if (array_key_exists('form_value', $exclude))
            {
                $formValue = $exclude['form_value'];
                if(isset($context[$formValue]))
                {
                    $exclude['value'] = $context[$formValue];
                }
                else
                {
                    $exclude['value'] = '';
                }

                $this->setExclude($exclude);
            }
        }

        return parent::isValid($value);
    }
}