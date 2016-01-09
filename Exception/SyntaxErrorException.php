<?php
/**
 * This file is part of Trinity package.
 */

namespace Trinity\Bundle\SearchBundle\Exception;

use Exception;


/**
 * Class SyntaxErrorException
 * @package Trinity\Bundle\SearchBundle\Exception
 */
class SyntaxErrorException extends Exception
{
    public function __construct($message = "") {
        $this->message = 'Syntax error: ' . $message;
    }
}