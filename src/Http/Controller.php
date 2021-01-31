<?php

namespace Cube\Http;

use Cube\Misc\Components;

abstract class Controller
{   
    /**
     * Controller constructor
     * 
     */
    public function __construct()
    {

    }

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
}