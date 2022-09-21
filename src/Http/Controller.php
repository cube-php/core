<?php

namespace Cube\Http;

use Cube\Misc\Components;

abstract class Controller
{   
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
        $request = Request::getRunningInstance();
        $result = $request->useMiddleware($data);

        if($result instanceof Response) {
            exit;
        }

        return $result;
    }
}