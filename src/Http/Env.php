<?php

namespace Cube\Http;

use Cube\App\App;
use Cube\App\Directory;
use Cube\Exceptions\AppException;
use Cube\Misc\File;

final class Env
{

    /**
     * Environment variables
     * 
     * @var string[]
     */
    private static $_main_vars = array();

    private static $_extra_vars = array();

    private static bool $_has_loaded_main = false;

    private static bool $_has_loaded_extras = false;

    /**
     * Return all enviroment variables
     *
     * @return string[]
     */
    public static function all()
    {
        return array_merge(
            self::load(),
            self::loadExtras()
        );
    }

    /**
     * Get Environment Variable
     *
     * @param string $name Variable name
     * @param mixed $default Default value if variable value is not found
     * @return mixed|null
     */
    public static function getMain($name, $default = null)
    {
        return static::load()[strtolower($name)] ?? $default;
    }

    /**
     * Get main var
     *
     * @param string $name
     * @param [type] $default
     * @return void
     */
    public static function get(string $name, $default = null)
    {
        return self::all()[strtolower($name)] ?? $default;
    }

    /**
     * Set env
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public static function set(string $name, mixed $value)
    {
        if (App::isProduction()) {
            throw new AppException('Cannot set environment variable in production mode');
        }

        $root = App::getPath(Directory::PATH_ROOT);
        $dev_env_file = $root . DIRECTORY_SEPARATOR . '.env.dev';

        if (!file_exists($dev_env_file)) {
            $file = new File($dev_env_file, true);
            $file->close();
        }

        $content = file_get_contents($dev_env_file);
        $name = strtoupper($name);
        $pattern = '/^' . preg_quote($name, '/') . '\s*=\s*(.*)$/m';
        $replacement = $name . '=' . (is_string($value) ? '"' . $value . '"' : $value) . PHP_EOL;

        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $replacement, $content);
        } else {
            $content .= PHP_EOL . $replacement;
        }

        file_put_contents($dev_env_file, $content);
        static::$_has_loaded_extras = false; // Reset extras cache
        static::$_extra_vars = []; // Reset extras vars
        static::$_main_vars = []; // Reset main vars
        static::$_has_loaded_main = false; // Reset main vars cache
        static::load(); // Reload main vars
        static::loadExtras(); // Reload extras vars
        static::$_main_vars[strtolower($name)] = $value; // Update main vars
    }

    /**
     * Check if environment variable exists
     *
     * @param string $name
     * @return boolean
     */
    public static function has(string $name): bool
    {
        return isset(static::$_main_vars[strtolower($name)]);
    }

    /**
     * Load up all environment variables
     * 
     * @return string[]
     */
    private static function load()
    {
        if (static::$_has_loaded_main) {
            return static::$_main_vars;
        }

        $root = App::getPath(Directory::PATH_ROOT);
        $env_file = $root . DIRECTORY_SEPARATOR . '.env';

        if (!file_exists($env_file)) {
            $file = new File($env_file, true);
            $file->write('');
            $file->close();
        }

        $all_vars = parse_ini_file($env_file);
        static::$_main_vars = array_change_key_case($all_vars, CASE_LOWER);
        static::$_has_loaded_main = true;

        return $all_vars;
    }

    /**
     * Load up all environment variables
     * 
     * @return string[]
     */
    private static function loadExtras()
    {
        if (static::$_has_loaded_extras) {
            return static::$_extra_vars;
        }

        $root = App::getPath(Directory::PATH_ROOT);
        $prod_env_file = $root . DIRECTORY_SEPARATOR . '.env.prod';
        $dev_env_file = $root . DIRECTORY_SEPARATOR . '.env.dev';

        $all_vars = [];

        if (file_exists($prod_env_file) && App::isProduction()) {
            $all_vars = array_merge($all_vars, parse_ini_file($prod_env_file));
        }

        if (file_exists($dev_env_file) && App::isDevelopment()) {
            $all_vars = array_merge($all_vars, parse_ini_file($dev_env_file));
        }

        $env_vars = array_change_key_case($all_vars, CASE_LOWER);
        static::$_extra_vars = $env_vars;
        static::$_has_loaded_extras = true;

        return $env_vars;
    }
}
