<?php

namespace Cube\Http;

use Cube\Misc\File;

final class Env
{
    
    /**
     * Environment variables
     * 
     * @var string[]
     */
    private static $_vars = array();

    /**
     * Return all enviroment variables
     *
     * @return string[]
     */
    public static function all()
    {
        return static::load();
    }

    /**
     * Get Environment Variable
     *
     * @param string $name Variable name
     * @param mixed $default Default value if variable value is not found
     * @return mixed|null
     */
    public static function get($name, $default = null)
    {
        static::load();

        $vars = static::$_vars;
        return $vars[strtolower($name)] ?? $default;
    }

    /**
     * Check if environment variable exists
     *
     * @param string $name
     * @return boolean
     */
    public static function has(string $name): bool
    {
        return isset(static::$_vars[strtolower($name)]);
    }

    /**
     * Load up all environment variables
     * 
     * @return string[]
     */
    private static function load()
    {
        if(static::$_vars) {
            return static::$_vars;
        }

        $env_file = APP_PATH . DS . '.env';

        if(!file_exists($env_file)) {
            $file = new File($env_file, true);
            $file->write('');
        }

        if(!static::$_vars) {
            $all_vars = parse_ini_file($env_file);
            static::$_vars = array_change_key_case($all_vars, CASE_LOWER);
        }

        return static::$_vars;
    }
}