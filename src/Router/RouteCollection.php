<?php

namespace Cube\Router;

use Cube\App\App;
use Cube\Exceptions\RouteNotFoundException;
use Cube\Router\Route;
use Cube\Http\Request;
use Cube\Http\Response;
use Cube\Interfaces\RequestInterface;
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
     * Class constructor
     * 
     */
    public function __construct(protected RequestInterface $request) {}

    /**
     * Build all routes
     * 
     * @return Response
     */
    public function build()
    {
        $request = $this->request;

        $raw_current_url = (string) $request->url()->getPath();
        $current_url = $this->trimPath($raw_current_url);

        /** @var Route|null */
        $matchedRoute = null;
        $routePathAttributes = [];

        foreach (static::$_attached_routes as $route) {

            if ($request->getMethod() !== $route->getMethod()) {
                continue;
            }

            $regex_path = $route->path()->regexp();
            $test = preg_match("#^{$regex_path}$#", $current_url, $matches);

            if ($test) {
                $matchedRoute = $route;
                $routePathAttributes = array_slice($matches, 1);
                break;
            }
        }

        if (!$matchedRoute) {

            EventManager::dispatchEvent(
                App::EVENT_ROUTE_MATCH_FOUND,
                $this->request
            );

            throw new RouteNotFoundException($this->request);
        }

        $route_attributes = $route->getAttributes();

        if ($routePathAttributes) {
            array_walk($route_attributes, function ($attribute, $index) use ($route, $routePathAttributes) {

                $name = $attribute->name;
                $value = RouteParser::attributeCast(
                    $routePathAttributes[$index],
                    $attribute->type,
                );

                if ($route->hasOptionalParameter()) {
                    $value = substr($value, 0, strlen($value) - 1);
                }

                $this->request->setAttribute($name, $value);
            });
        }

        $response = $matchedRoute->parseResponse(new Response());
        $result = $route->handle($request, $response);

        //Dispatch event when request is completed
        EventManager::dispatchEvent(
            Request::EVENT_COMPLETED,
            $this->request
        );

        //Initialize controller
        return $result;
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
