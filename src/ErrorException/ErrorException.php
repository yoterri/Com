<?php

namespace Com\ErrorException;

class ErrorException extends \ErrorException
{

    function __construct($message, $code, $errFile = null, $errLine = null)
    {
        parent::__construct($message, 0, $code, $errFile, $errLine);
    }
}