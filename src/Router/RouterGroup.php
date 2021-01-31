<?php

namespace Cube\Router;

class RouterGroup extends Router
{
    /**
     * Set group's namespace
     *
     * @param string $namespace
     * @return self
     */
    public function namespace(string $namespace)
    {
        $this->setNamespace($namespace);
        return $this;
    }

    /**
     * Register new routes in router group
     *
     * @param Closure $callback
     * @return self
     */
    public function register($callback)
    {
        $callback($this);
        return $this;
    }

    /**
     * Set middlewares to use
     *
     * @param string|array $middleware
     * @return self
     */
    public function use($middleware)
    {
        $this->setMiddleware($middleware);
        return $this;
    }
}