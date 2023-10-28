<?php

namespace Cube\Exceptions;

use Exception;
use Throwable;

class DBException extends Exception
{
    public const CONNECTION_NOT_FOUND = -1;
    public const CONNECTION_FAILED = -2;

    /**
     * Constructor
     *
     * @param string $message
     * @param int $code
     * @param Throwable $previous
     */
    public function __construct($message, $code = self::CONNECTION_FAILED, $previous = null)
    {
        $message = 'Database error: ' . $message;
        parent::__construct($message, $code, $previous);
    }
}
