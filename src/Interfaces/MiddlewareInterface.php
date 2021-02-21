<?php

namespace Cube\Interfaces;

use Cube\Http\Request;

interface MiddlewareInterface
{
    public function trigger(Request $request, ?array $args = null);
}