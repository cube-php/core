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
        return app(Request::class)->getSessionManager()->getName();
    }

    /**
     * Regenerate session id
     * 
     * @return string New session id
     */
    public static function regenerate()
    {
        $request = app(Request::class);
        self::handler();

        $request->getSessionManager()->regenerateId(
            $request->session()
        );
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

    /**
     * Get session handler
     *
     * @return SessionHandler
     */
    protected static function handler(): SessionHandler
    {
        return app(Request::class)->session();
    }
}
