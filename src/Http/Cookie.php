<?php

namespace Cube\Http;

use InvalidArgumentException;

class Cookie
{
    /**
     * Cookie constructor
     * 
     */
    public function __construct()
    {
    }

    /**
     * Set new cookie
     * 
     * @param string $name Cookie name
     * @param string $value Cookie value
     * @param float|int $expires Cookie duration
     * @param string $path
     * 
     * @return void
     */
    public static function set($name, $value, $expires = (7*24*60*60), $path = '/') {
        setcookie($name, $value, (time() + $expires), $path);
        return true;
    }

    /**
     * Check if cookie exists
     *
     * @param string $name Cookie name
     * @return boolean
     */
    public static function has($name)
    {
        return isset($_COOKIE[$name]);
    }

    /**
     * Return cookie's value
     *
     * @param string $name Cookie name
     * @return mixed|null
     */
    public static function get($name)
    {
        if(!static::has($name)) {
            return null;
        }

        return $_COOKIE[$name];
    }

    /**
     * Get cookie value and remove it
     *
     * @param string $name Cookie key
     * @return mixed
     */
    public static function getAndRemove($name)
    {
        $data = static::get($name);
        static::remove($name);

        return $data;
    }

    /**
     * Remove cookie
     * 
     * @param string $name Cookie name
     * 
     * @return void;
     */
    public static function remove($name)
    {
        self::set($name, null, time() - 300);
    }
}