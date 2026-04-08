<?php

use Cube\App\Container;

/**
 * Get the available container instance.
 *
 * @param  string|null  $abstract
 * @return Container|mixed
 */
function app(?string $abstract = null)
{
    static $container;

    if (!$container) {
        $container = new Container();
    }

    return $abstract
        ? $container->make($abstract)
        : $container;
}
