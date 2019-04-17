<?php

namespace Com\Validator;

use Zend\Validator\AbstractValidator;

class PathWritable extends AbstractValidator
{
    
    const NOT_WRITABLE = 'not_writable';
    const DOES_NOT_EXISTS = 'does_not_exists';
    CONST NOT_SPECIFIED = 'not_specified';


    protected $messageTemplates = array(
        self::NOT_WRITABLE => 'Upload path is not writable',
        self::DOES_NOT_EXISTS => 'Upload path does not exists',
        self::NOT_SPECIFIED => 'Upload path was not specified',
    );


    protected $path;


    function setPath($path)
    {
        $this->path = $path;
    }


    function getPath()
    {
        return $this->path;
    }



    public function isValid($value)
    {
        
        $this->setValue($value);

        $dir = $this->getPath();

        if(!$dir)
        {
            $this->error(self::NOT_SPECIFIED);
            return false;
        }

        if(!file_exists($dir))
        {
            $this->error(self::DOES_NOT_EXISTS);
            return false;
        }

        if(!is_writable($dir))
        {
            $this->error(self::NOT_WRITABLE);
            return false;
        }

        return true;
    }
}