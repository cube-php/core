<?php

namespace Cube\Exceptions;

use Cube\Http\Request;
use Exception;

class RouteNotFoundException extends Exception
{
    public function __construct(Request $request)
    {
        parent::__construct('Not found', 404);
    }
}
