<?php

namespace Cube\Router;

readonly class RouteMatchResult
{
    public function __construct(
        public ?Route $route = null,
        public array $params = [],
    ) {}
}
