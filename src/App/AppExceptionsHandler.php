<?php

namespace Cube\App;

use Cube\Http\Response;
use Cube\Interfaces\RequestInterface;
use Cube\Misc\Collection;
use Throwable;

class AppExceptionsHandler
{
    public Collection $handlers;

    public function __construct()
    {
        $this->handlers = new Collection();
    }

    /**
     * Register an handler
     *
     * @param string $handler
     * @param callable $fn
     * @return void
     */
    public function on(string $handler, callable $fn)
    {
        $this->handlers->set($handler, $fn);
    }

    public function handle(Throwable $exception, RequestInterface $request)
    {
        $class = get_class($exception);
        if (!$this->handlers->has($class)) {
            return response()
                ->withStatusCode(Response::HTTP_SERVICE_UNAVAILABLE)
                ->write(h('center', null, 'Service Unavailable'));
        }

        $fn = $this->handlers->get($class);
        return $fn($request, $exception);
    }
}
