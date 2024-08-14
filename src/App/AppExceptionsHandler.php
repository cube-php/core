<?php

namespace Cube\App;

use Closure;
use Cube\Http\Request;
use Cube\Http\Response;
use Cube\Interfaces\RequestInterface;
use Cube\Misc\Collection;
use Error;
use Throwable;

class AppExceptionsHandler
{
    /**
     * Exception handlers collection
     *
     * @var Collection
     */
    protected Collection $handlers;

    /**
     * On exception callback
     *
     * @var Closure|null
     */
    protected ?Closure $on_exception = null;

    /**
     * Constructor
     */
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

    /**
     * Set exception thrown
     *
     * @param Closure $fn
     * @return void
     */
    public function onExceptionThrown(Closure $fn)
    {
        $this->on_exception = $fn;
    }

    /**
     * Handle exception
     *
     * @param RequestInterface $request
     * @param Throwable $exception
     * @return Response
     */
    public function handle(RequestInterface $request, Throwable $exception): Response
    {
        $this->setDefault($request, $exception);
        $on_exception = $this->on_exception;

        if ($on_exception && App::isDevelopment()) {
            $on_exception($request, $exception);
        }

        $class = get_class($exception);
        if (!$this->handlers->has($class)) {
            return $this->handlers->get('default')($request, $exception);
        }

        $fn = $this->handlers->get($class);
        return $fn($request, $exception);
    }

    /**
     * Set default handler
     *
     * @param callable $fn
     * @return void
     */
    public function default(callable $fn)
    {
        $this->handlers->set('default', $fn);
    }

    /**
     * Default handler
     *
     * @return void
     */
    private function setDefault(Request $request, Throwable $exception)
    {
        $this->default(function () use ($exception) {
            if (App::isDevelopment()) {
                throw $exception;
            }

            response()
                ->withStatusCode(Response::HTTP_SERVICE_UNAVAILABLE)
                ->write(h('center', null, 'Service Unavailable'));
        });
    }
}
