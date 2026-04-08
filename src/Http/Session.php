<?php

namespace Cube\Http;

use Cube\Http\Session\SessionHandler;

class Session
{

    /**
     * Check if session exists
     *
     * @param string $name Session name 
     * 
     * @return boolean
     */
    public static function has($name)
    {
        return self::handler()->has($name);
    }

    /**
     * Get session value
     * 
     * @param string $name Session name
     * 
     * @return mixed|null
     */
    public static function get($name)
    {
        if (!static::has($name)) {
            return null;
        }

        return self::handler()->get($name);
    }

    /**
     * Get session value and remove it
     *
     * @param string $name Session key name
     * @return mixed
     */
    public static function getAndRemove($name)
    {
        $data = static::get($name);
        static::remove($name);

        return $data;
    }

    /**
     * Returns session name
     * 
     * @return string
     */
    public static function name()
    {
        return session_name();
    }

    /**
     * Regenerate session name
     * 
     * @return string New session id
     */
    public static function regenerate()
    {
        session_regenerate_id();
        return session_id();
    }

    /**
     * Remove session
     *
     * @param string $name Session name
     * 
     * @return bool
     */
    public static function remove($name)
    {
        if (!static::has($name)) {
            return false;
        }

        self::handler()->remove($name);
        return true;
    }

    /**
     * Set new session
     *
     * @param string $name Session name
     * @param string $value Session value
     * 
     * @return bool
     */
    public static function set($name, $value)
    {
        self::handler()->put($name, $value);
    }

    protected static function handler(): SessionHandler
    {
        return app(SessionHandler::class);
    }
}
