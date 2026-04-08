<?php

namespace Cube\App;

use Closure;
use RuntimeException;

final class Container
{
    private array $bindings = [];
    private array $singletons = [];
    private array $singletonInstances = [];

    private array $scoped = [];
    private array $scopedInstances = [];

    /**
     * Bind a transient service
     *
     * @param string $abstract
     * @param Closure $factory
     * @return void
     */
    public function bind(string $abstract, Closure $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    /**
     * Bind a singleton service
     *
     * @param string $abstract
     * @param Closure $factory
     * @return void
     */
    public function singleton(string $abstract, Closure $factory): void
    {
        $this->singletons[$abstract] = $factory;
    }

    /**
     * Bind a request-scoped service
     *
     * @param string $abstract
     * @param Closure $factory
     * @return void
     */
    public function scoped(string $abstract, Closure $factory): void
    {
        $this->scoped[$abstract] = $factory;
    }

    /**
     * Resolve a service
     *
     * @param string $abstract
     * @return mixed
     * @throws RuntimeException
     */
    public function make(string $abstract): mixed
    {
        // request scoped
        if (isset($this->scoped[$abstract])) {
            if (!isset($this->scopedInstances[$abstract])) {
                $this->scopedInstances[$abstract] =
                    $this->scoped[$abstract]($this);
            }

            return $this->scopedInstances[$abstract];
        }

        // singleton
        if (isset($this->singletons[$abstract])) {
            if (!isset($this->singletonInstances[$abstract])) {
                $this->singletonInstances[$abstract] =
                    $this->singletons[$abstract]($this);
            }

            return $this->singletonInstances[$abstract];
        }

        // transient binding
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]($this);
        }

        throw new RuntimeException("Nothing bound for [$abstract]");
    }

    public function resetScoped(): void
    {
        $this->scopedInstances = [];
    }
}
