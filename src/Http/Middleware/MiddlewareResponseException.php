<?php

namespace Cube\Http\Middleware;

use Cube\Http\Response;
use RuntimeException;

final class MiddlewareResponseException extends RuntimeException
{
    /**
     * Create an exception that stops controller execution with a middleware response.
     *
     * @param Response $response Middleware response
     */
    public function __construct(private Response $response)
    {
        parent::__construct('Middleware returned a response');
    }

    /**
     * Get the middleware response.
     *
     * @return Response
     */
    public function response(): Response
    {
        return $this->response;
    }
}
