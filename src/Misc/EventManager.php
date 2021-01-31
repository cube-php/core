<?php

namespace Cube\Misc;

use InvalidArgumentException;

class EventManager
{
    /**
     * Registered events
     * 
     * @var array
     */
    private static $events = array();

    /**
     * Class constructor
     * 
     */
    public function __construct() {}

    /**
     * Make events callable statically
     * 
     * @param string $name Event name
     * @param array $args
     * 
     * @return void
     */
    public static function __callStatic($name, $args)
    {

        if(count($args) > 1) {
            throw new InvalidArgumentException
                ($name . ' should contain just 1 argument which is the callback function');
        }

        $callback = $args[0] ?? null;

        if(!is_callable($callback)) {
            throw new InvalidArgumentException
                ($name . '\'s argument should is not a valid callback function');
        }

        return static::on($name, $callback);
    }

    /**
     * Run all functions called on event
     * 
     * @return void
     */
    public static function dispatchEvent($handler, $arg = null)
    {
        if(!static::hasAttachedEvents($handler)) return false;

        $handles = static::$events[$handler];

        foreach($handles as $function) {
            if(is_callable($function)) {
                $function($arg);
                continue;
            }

            if(is_string($function)) {
                $function::handle($arg);
                continue;
            }
        }
    }

    /**
     * Check if handler has events
     * 
     * @return bool
     */
    public static function hasAttachedEvents($handler)
    {
        return (
            isset(static::$events[$handler]) &&
            is_array(static::$events[$handler])
        );
    }

    /**
     * Add new event listener
     * 
     * @param int|string $handler Event Handler
     * @param callable|string $callback Callback function
     * 
     * @return void
     */
    public static function on($handler, $callback)
    {
        if(!isset(static::$events[$handler])) static::$events[$handler] = array();

        if(is_callable($callback)) {
            return static::$events[$handler][] = $callback;
        }

        if(is_string($callback) && !class_exists($callback)) {
            throw new InvalidArgumentException("Class \"{$callback}\" does not exist");
        }

        if(!method_exists($callback, 'handle')) {
            throw new InvalidArgumentException("Class \"{$callback}\" does not have method \"handle\"");
        }

        return static::$events[$handler][] = $callback;
    }
}