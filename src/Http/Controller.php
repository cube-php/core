<?php

namespace Cube\Http;

use Cube\Misc\Components;

abstract class Controller
{
    protected array $middlewares = [];

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
        if (is_array($data)) {
            return $this->middlewares = array_merge(
                $this->middlewares,
                $data
            );
        }

        //TODO: Invoke middleware on assignment
        $this->middlewares[] = $data;
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
