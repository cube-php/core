<?php

namespace Cube\Router;

use Cube\App;

use Cube\Router\Route;
use Cube\Http\Request;
use Cube\Http\Response;
use Cube\Misc\EventManager;

class RouteCollection
{

    /**
     * Routes collection
     * 
     * @var Route[]
     */
    private static $_routes = array();

    /**
     * Named routes
     *
     * @var Route[]
     */
    private static $_name_routes = array();

    /**
     * Routes on request method
     * 
     * @var Route[]
     */
    private static $_attached_routes = array();

    /**
     * Request interface
     * 
     * @var Request
     */
    private $_request;

    /**
     * Class constructor
     * 
     */
    public function __construct()
    {
        $this->_request = new Request();
    }

    /**
     * Build all routes
     * 
     * @return void
     */
    public function build()
    {

        $path_match_found = false;
        $raw_current_url = (string) $this->_request->url()->getPath();
        $current_url = $this->trimPath($raw_current_url);

        foreach(static::$_attached_routes as $route)
        {
            
            if($path_match_found) return true;

            #Get route regex path
            $regex_path = $route->path()->regexp();

            #Test current url
            $test = preg_match("#^{$regex_path}$#", $current_url, $matches);

            #Match found!!!
            if($test) {

                $path_match_found = true;
                $path_attributes = array_slice($matches, 1);
                $route_attributes = $route->getAttributes();

                array_walk($route_attributes, function($attribute, $index) use ($path_attributes, $route) {

                    $name = $attribute;
                    $value = $path_attributes[$index] ?? null;

                    if($route->hasOptionalParameter()) {
                        $value = substr($value, 0, strlen($value) - 1);
                    }

                    $this->_request->setAttribute($name, $value);
                });

                #Do any other events when route is matched
                EventManager::dispatchEvent(App::EVENT_ROUTE_MATCH_FOUND, $this->_request);
                
                #Get parsed response
                $response = $route->parseResponse(Response::getInstance());

                #Engage Middlewares
                $request = $route->engageMiddleware($this->_request);

                if(!$request) {
                    return true;
                }

                #Instantiate route controller
                $route->initController($request, $response);
                
                return true;
            }
        }

        #Oh no, no matches
        EventManager::dispatchEvent(App::EVENT_ROUTE_NO_MATCH_FOUND, $this->_request);
    }

    /**
     * Trim route path
     *
     * @param string $path
     * @return string
     */
    public function trimPath($path)
    {
        $path = preg_replace('#([\/]{1,})#', '/', $path);
        $last_char = strlen($path) == 1 ? $path : substr($path, -1, 1);
        return $last_char == '/' ? $path : $path . '/';
    }

    /**
     * Get all routes
     *
     * @return array
     */
    public static function all()
    {
        return self::$_routes;
    }

    /**
     * Get route from name
     *
     * @param string $name
     * @return Route|null
     */
    public static function getRouteFromName(string $name): ?Route
    {
        return self::$_name_routes[$name] ?? null;
    }

    /**
     * Attach new route to collection
     * 
     * @param Route $route Route to attach
     */
    public static function attachRoute(Route $route)
    {
        #Attach route to all routes
        static::$_routes[] = $route;

        $request = new Request();

        #attach on request method
        if($route->getMethod() && $request->getMethod() !== $route->getMethod()) {
            return $route;
        }
        static::$_attached_routes[] = $route;
        return $route;
    }

    /**
     * Bind a named route
     *
     * @param Route $route
     * @return void
     */
    public static function bindNamedRoute(Route $route)
    {
        self::$_name_routes[$route->getName()] = $route;
    }
}