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
    /**
     * SyntaxErrorException constructor.
     * @param string $message
     */
    public function __construct($message = '')
    {
        parent::__construct('Syntax error: ' . $message);
    }
}
