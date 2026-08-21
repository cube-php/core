<?php

namespace Cube\Http\Middleware;

use InvalidArgumentException;

final class MiddlewareResolver
{
    public const ARGS_DELIMITER = ':';

    /**
     * Create a middleware resolver.
     *
     * @param array $aliases Middleware aliases from application config
     */
    public function __construct(private array $aliases = [])
    {
        $this->validateAliases($aliases);
    }

    /**
     * Normalize one middleware declaration or a list of declarations.
     *
     * @param mixed $middleware Middleware class name, alias, callable, object, or list
     * @return MiddlewareDefinition[]
     */
    public function resolve(mixed $middleware): array
    {
        if (is_array($middleware)) {
            $resolved = [];

            foreach ($middleware as $item) {
                $resolved = array_merge($resolved, $this->resolve($item));
            }

            return $resolved;
        }

        if (is_string($middleware)) {
            return $this->resolveString($middleware);
        }

        return [new MiddlewareDefinition(
            handler: $middleware,
            name: $this->identifier($middleware),
        )];
    }

    /**
     * Resolve a string middleware class or alias and parse its route arguments.
     *
     * @param string $middleware Middleware declaration such as "auth:admin"
     * @return MiddlewareDefinition[]
     */
    private function resolveString(string $middleware): array
    {
        [$key, $args] = $this->parseString($middleware);
        $handler = class_exists($key) ? $key : ($this->aliases[$key] ?? null);

        if (!$handler) {
            throw new InvalidArgumentException('Middleware "' . $key . '" is not assigned');
        }

        if (is_array($handler)) {
            return $this->resolve($handler);
        }

        return [new MiddlewareDefinition(
            handler: $handler,
            args: $args,
            name: $this->identifier($handler, $key),
        )];
    }

    /**
     * Split a string middleware declaration into its lookup key and argument list.
     *
     * @param string $middleware Middleware declaration such as "auth:admin,write"
     * @return array{0: string, 1: array|null}
     */
    private function parseString(string $middleware): array
    {
        $vars = explode(self::ARGS_DELIMITER, $middleware, 2);
        $args = isset($vars[1]) && $vars[1] !== ''
            ? explode(',', $vars[1])
            : null;

        return [$vars[0], $args];
    }

    /**
     * Ensure alias names do not conflict with route-level argument syntax.
     *
     * @param array $aliases Middleware aliases from application config
     * @return void
     */
    private function validateAliases(array $aliases): void
    {
        array_walk($aliases, function (mixed $class, string|int $key) {
            if (strpos((string) $key, self::ARGS_DELIMITER)) {
                throw new InvalidArgumentException('Middleware keys must not contain ' . self::ARGS_DELIMITER);
            }
        });
    }

    /**
     * Get a stable identifier for middleware execution tracking.
     *
     * @param mixed $handler Middleware handler
     * @param string|null $fallback Fallback identifier
     * @return string
     */
    private function identifier(mixed $handler, ?string $fallback = null): string
    {
        if (is_object($handler)) {
            return $handler::class;
        }

        if (is_string($handler)) {
            return explode(self::ARGS_DELIMITER, $handler, 2)[0];
        }

        if (is_array($handler)) {
            $target = $handler[0] ?? null;
            $method = $handler[1] ?? null;
            $target = is_object($target) ? $target::class : (string) $target;

            return $method ? $target . '::' . $method : $target;
        }

        return $fallback ?: 'callable';
    }
}
