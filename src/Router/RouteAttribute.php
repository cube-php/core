<?php

namespace Cube\Router;

readonly class RouteAttribute
{
    public function __construct(
        public string $name,
        public mixed $type
    ) {}
}
