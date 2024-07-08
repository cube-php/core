<?php

namespace Cube\Router\Attributes;

use Attribute;

#[Attribute]
readonly class Put
{
    public function __construct(
        public string $path = '/',
        public ?string $name = null,
        public array $use = [],
    ) {
    }
}
