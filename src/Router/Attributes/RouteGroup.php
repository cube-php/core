<?php

namespace Cube\Router\Attributes;

use Attribute;

#[Attribute]
readonly class RouteGroup
{
    public function __construct(
        public string $path,
        public ?string $name = null,
        /* @deprecated 0.2.0 Use $middleware instead */
        public ?array $use = null,
        public ?array $middleware = null,
        public ?array $withoutMiddleware = null,
    ) {}
}
