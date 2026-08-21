<?php

namespace Cube\Http\Middleware;

use Cube\Http\Request;
use Cube\Http\Response;
use Cube\Interfaces\MiddlewareInterface;
use InvalidArgumentException;

final class MiddlewarePipeline
{
    /**
     * Create a middleware pipeline.
     *
     * @param MiddlewareResolver $resolver Middleware declaration resolver
     */
    public function __construct(private MiddlewareResolver $resolver = new MiddlewareResolver()) {}

    /**
     * Run middleware against a request until all pass or one returns a response.
     *
     * @param Request $request Current request
     * @param mixed $middlewares Middleware class name, alias, callable, object, or list
     * @return Request|Response
     */
    public function through(Request $request, mixed $middlewares): Request|Response
    {
        $result = $request;

        foreach ($this->resolver->resolve($middlewares) as $middleware) {
            $result = $this->handle($result, $middleware);

            if ($result instanceof Response) {
                $this->persistSession($request, $result);
                break;
            }
        }

        return $result;
    }

    /**
     * Execute one normalized middleware definition.
     *
     * @param Request $request Current request
     * @param MiddlewareDefinition $middleware Normalized middleware definition
     * @return Request|Response
     */
    private function handle(Request $request, MiddlewareDefinition $middleware): Request|Response
    {
        $handler = $middleware->handler;

        if (is_object($handler) && is_a($handler, MiddlewareInterface::class)) {
            $request->addCalledMiddleware($middleware->name ?? $this->identifier($handler));
            return $handler->trigger($request, $middleware->args);
        }

        if (is_callable($handler)) {
            $request->addCalledMiddleware($middleware->name ?? $this->identifier($handler));
            return $handler($request, $middleware->args);
        }

        if (!is_string($handler) || !is_a($handler, MiddlewareInterface::class, true)) {
            throw new InvalidArgumentException(
                sprintf('"%s" is not a middleware', is_scalar($handler) ? $handler : get_debug_type($handler))
            );
        }

        $request->addCalledMiddleware($middleware->name ?? $this->identifier($handler));
        return call_user_func_array([new $handler(), 'trigger'], [$request, $middleware->args]);
    }

    /**
     * Persist session changes when middleware short-circuits with a response.
     *
     * @param Request $request Current request
     * @param Response $response Middleware response
     * @return void
     */
    private function persistSession(Request $request, Response $response): void
    {
        $sessionManager = $request->getSessionManager();

        if (!$sessionManager) {
            return;
        }

        $sessionManager->persist(
            $request->session(),
            $response
        );
    }

    /**
     * Get a stable identifier for middleware execution tracking.
     *
     * @param mixed $handler Middleware handler
     * @return string
     */
    private function identifier(mixed $handler): string
    {
        if (is_object($handler)) {
            return $handler::class;
        }

        if (is_array($handler)) {
            $target = $handler[0] ?? null;
            $method = $handler[1] ?? null;
            $target = is_object($target) ? $target::class : (string) $target;

            return $method ? $target . '::' . $method : $target;
        }

        return (string) $handler;
    }
}
