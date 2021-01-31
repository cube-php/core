<?php

namespace Cube\Interfaces;

use Cube\Http\Request;

use Cube\Http\Response;

interface MiddlewareInterface
{
    public function trigger(Request $request, ?array $args = null);
}