<?php

namespace Cube\Http\Middlewares;

use Cube\Http\Request;
use Cube\Interfaces\MiddlewareInterface;

class UrlHistoryUpdateMiddleware implements MiddlewareInterface
{
    public function trigger(Request $request, ?array $args = null)
    {
        $request->updateUrlHistory();
        return $request;
    }
}
