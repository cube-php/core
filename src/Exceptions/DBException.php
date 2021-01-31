<?php

namespace Cube\Exceptions;

use Exception;
use Throwable;

class DBException extends Exception
{
    /**
     * Constructor
     *
     * @param string $message
     * @param int $code
     * @param Throwable $previous
     */
    public function __construct($message, $code = null, $previous = null)
    {
        $message = 'Database error: ' . $message;
        parent::__construct($message, $code, $previous);
    }
}