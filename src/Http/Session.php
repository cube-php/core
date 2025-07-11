<?php

namespace Cube\Http;

use Cube\Modules\SessionManager;

class Session
{
    /**
     * Session name
     *
     * @var string
     */
    private static $_cookie_name = 'CUBESESSIDX';

    /**
     * Session instance
     *
     * @var self
     */
    private static $_instance = null;

    /**
     * Session constructor
     * 
     * 
     */
    private function __construct()
    {
        if (SessionManager::isReady()) {
            $handler = new SessionManager();
            session_set_save_handler($handler, true);
        }

        /**
         * Set session name
         */
        session_name(static::$_cookie_name);

        /**
         * Start session
         */
        session_start();

        if (!self::has(self::$_cookie_name)) {
            self::set(self::$_cookie_name, generate_token(30));
        }
    }

    /**
     * Creates and starts session
     *
     * @return void
     */
    public static function createInstance()
    {
        if (!static::$_instance) {
            static::$_instance = new self;
        }

        return static::$_instance;
    }

    /**
     * Check if session exists
     *
     * @param string $name Session name 
     * 
     * @return boolean
     */
    public static function has($name)
    {
        return isset($_SESSION[$name]);
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

        return $_SESSION[$name];
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

        unset($_SESSION[$name]);
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
        $_SESSION[$name] = $value;
        return true;
    }
}
