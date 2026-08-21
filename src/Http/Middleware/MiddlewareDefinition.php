<?php

namespace Cube\Http\Middleware;

final readonly class MiddlewareDefinition
{
    /**
     * Normalized middleware data passed from the resolver to the pipeline.
     *
     * @param mixed $handler Middleware class name, object, or callable
     * @param array|null $args Parsed route-level middleware arguments
     * @param string|null $name Stable identifier used for executed middleware tracking
     */
    public function __construct(
        public mixed $handler,
        public ?array $args = null,
        public ?string $name = null,
    ) {}
}
