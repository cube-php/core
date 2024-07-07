<?php

namespace Cube\Router\Attributes;

use Attribute;

#[Attribute]
readonly class Delete
{
    public function __construct(
        public string $path,
        public ?string $name = null,
        public array $use = [],
    ) {
    }
}
