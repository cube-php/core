<?php

namespace Cube\Router\Attributes;

use Attribute;

#[Attribute()]
readonly class Route
{
    public function __construct(
        public string $method,
        public string $path,
        public ?string $name = null,
        /* @deprecated 0.2.0 Use $middleware instead */
        public array $use = [],
        public array $middleware = [],
        public ?array $withoutMiddleware = null,
    ) {}
}
