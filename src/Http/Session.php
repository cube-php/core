<?php

namespace Cube\Http;

use Cube\App\App;
use Cube\Modules\Sessions\DBSessionManager;

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
    private static $configured = null;

    /**
     * Session constructor
     * 
     * 
     */
    public function __construct()
    {
        if (!self::$configured) {
            $config = App::getConfig('app.session.handler', null);

            match ($config) {
                'database' => session_set_save_handler(
                    new DBSessionManager(),
                    true
                ),
                default => null
            };

            session_name(static::$_cookie_name);
            self::$configured = true;
        }

        session_start();

        if (!self::has(self::$_cookie_name)) {
            self::set(self::$_cookie_name, generate_token(30));
        }
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
