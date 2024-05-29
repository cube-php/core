<?php

namespace Cube\Router\Attributes;

use Attribute;

#[Attribute]
readonly class RouteGroup
{
    public function __construct(
        public string $path,
        public ?string $name = null,
        public ?array $use = null
    ) {
    }
}
