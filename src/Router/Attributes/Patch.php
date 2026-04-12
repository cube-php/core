<?php

namespace Cube\Router\Attributes;

use Attribute;

#[Attribute]
readonly class Patch
{
    public function __construct(
        public string $path = '/',
        public ?string $name = null,
        /* @deprecated 0.2.0 Use $middleware instead */
        public array $use = [],
        public array $middleware = [],
    ) {}
}
