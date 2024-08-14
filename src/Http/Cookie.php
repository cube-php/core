<?php

namespace Cube\Http;

class Cookie
{
    protected static $queue_name = 'cube::HttpCookiesQueue';

    /**
     * Set new cookie
     * 
     * @param string $name Cookie name
     * @param string $value Cookie value
     * @param float|int $expires Cookie duration
     * @param string $path
     * 
     * @return bool
     */
    public static function set(
        string $name,
        string $value,
        int $expires = 0,
        $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httponly = false
    ): bool {
        $cookies = self::getQueue();
        $cookies[] = (object) array(
            'name' => $name,
            'value' => $value,
            'expires' => $expires,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httponly
        );

        Session::set(static::$queue_name, $cookies);
        return true;
    }

    /**
     * Get queue
     *
     * @return array
     */
    public static function getQueue(): array
    {
        $cookies = Session::getAndRemove(static::$queue_name) ?? [];
        return $cookies;
    }

    /**
     * Clear cookie queue
     *
     * @return void
     */
    public static function clearQueue(): void
    {
        Session::remove(self::$queue_name);
    }

    /**
     * Set cookie if it does not exist
     *
     * @param string $name
     * @param string $value
     * @param int $expires
     * @param string $path
     * @return bool
     */
    public static function setIfNotExists($name, $value, $expires = null, $path = '/'): bool
    {
        if (static::has($name)) {
            return true;
        }

        $expires = $expires ?? getdays(7);
        return static::set($name, $value, $expires, $path);
    }

    /**
     * Get cookie if it exists or set new cooke via callable $fn
     *
     * @param string $name
     * @param callable $fn
     * @param int|null $expires
     * @param string $path
     * @return mixed
     */
    public static function getOrSet($name, callable $fn, $expires = null, $path = '/')
    {
        $data = Cookie::get($name);

        if ($data) {
            return $data;
        }

        $data = $fn();
        static::set($name, $data, $expires, $path);

        return $data;
    }

    /**
     * Check if cookie exists
     *
     * @param string $name Cookie name
     * @return boolean
     */
    public static function has($name)
    {
        return Request::getCurrentRequest()->getCookies()->has($name);
    }

    /**
     * Return cookie's value
     *
     * @param string $name Cookie name
     * @return mixed|null
     */
    public static function get($name)
    {
        if (!static::has($name)) {
            return null;
        }

        return Request::getCurrentRequest()->getCookies()->get($name);
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
        self::set($name, '', time() - 300);
    }
}
