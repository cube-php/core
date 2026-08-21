<?php

namespace Cube\Http;

use Cube\Http\Middleware\MiddlewarePipeline;
use Cube\Http\Middleware\MiddlewareResponseException;
use Cube\Misc\Components;

abstract class Controller
{
    protected array $middlewares = [];

    private bool $should_execute_middleware = false;

    private ?Response $middleware_response = null;

    /**
     * Create a controller instance for the current request.
     *
     * @param Request|null $request Current request
     * @param Response|null $response Current response
     */
    public function __construct(
        protected ?Request $request = null,
        protected ?Response $response = null,
    ) {}

    /**
     * Get component
     *
     * @param string $name Component name
     * @param array $args Component arguments
     * @return mixed
     */
    public function getComponent(string $name, array $args = [])
    {
        return Components::get($name, $args);
    }

    /**
     * Use middleware
     *
     * @param string|array|callable $data
     * @return mixed
     */
    public function middleware($data)
    {
        if ($this->should_execute_middleware && $this->request) {
            $result = app(MiddlewarePipeline::class)->through($this->request, $data);

            if ($result instanceof Response) {
                $this->middleware_response = $result;
                throw new MiddlewareResponseException($result);
            }

            return $result;
        }

        if (is_array($data)) {
            return $this->middlewares = array_merge(
                $this->middlewares,
                $data
            );
        }

        $this->middlewares[] = $data;
    }

    /**
     * Execute future middleware calls immediately.
     *
     * @return void
     */
    public function executeMiddlewareOnCall(): void
    {
        $this->should_execute_middleware = true;
    }

    /**
     * Get the response returned by an immediately executed middleware.
     *
     * @return Response|null
     */
    public function getMiddlewareResponse(): ?Response
    {
        return $this->middleware_response;
    }

    /**
     * Get "in-controller" assigned middlewares
     *
     * @return array
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}
