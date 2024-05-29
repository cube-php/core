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
        public array $use = []
    ) {
    }
}
