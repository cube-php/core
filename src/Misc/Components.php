<?php

namespace Cube\Misc;

use Cube\Exceptions\ComponentsException;

final class Components
{
    /**
     * All registered components
     *
     * @var array
     */
    private static $_registered_components = [];

    /**
     * Retrieve instance of declared component
     *
     * @param string $name Component name
     * @return mixed
     */
    public static function get(string $name, array $args = [])
    {
        if(!static::isRegistered($name)) {
            throw new ComponentsException('Component "' . $name . '" is not declared');
        }

        $name = strtolower($name);
        $value = static::$_registered_components[$name];

        if(is_callable($value)) {
            return call_user_func_array($value, $args);
        }

        return $value;
    }

    /**
     * Check if component is registered
     *
     * @param string $name Component name
     * @return boolean
     */
    public static function isRegistered(string $name) : bool
    {
        $name = strtolower($name);
        return in_array($name, array_keys(static::$_registered_components));
    }

    /**
     * Register a new component
     *
     * @param string $name Component
     * @param mixed $value Component content
     * @return boolean
     */
    public static function register(string $name, $value) : bool
    {
        if(self::isRegistered($name)) {
            throw new ComponentsException('Component "' . $name . '" has already been declared');
        }

        $name = strtolower($name);
        static::$_registered_components[$name] = $value;
        return true;
    }
}