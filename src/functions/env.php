<?php

/**
 * ======================================
 * Enviroment methods should go here
 * ======================================
 */

use Cube\Http\Env;

/**
 * Retrieve environment variable
 *
 * @param string $name Variable name
 * @param string $default Default value if variable is not set
 * @return mixed Variable value or default
 */
function env($name, $default = null) {
    return Env::get($name, $default);
}