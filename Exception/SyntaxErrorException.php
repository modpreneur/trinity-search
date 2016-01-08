<?php

namespace Trinity\SearchBundle\Exception;

use Exception;

class SyntaxErrorException extends Exception
{
    public function __construct($message = "") {
        $this->message = 'Syntax error: ' . $message;
    }
}