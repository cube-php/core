<?php

namespace Cube\Router;

use Cube\Exceptions\RouteNotFoundException;
use Cube\Router\Route;
use Cube\Http\Request;
use Cube\Http\Response;
use Cube\Interfaces\RequestInterface;
use Cube\Misc\EventManager;
use InvalidArgumentException;

class RouteCollection
{

    /** @var Route[] */
    protected static $all_routes = array();

    /** @var array<string, array<string, Route>> */
    protected static $static_routes = array();

    /** @var array<string, array<int, array{route: Route, regex: string}>> */
    protected static $dynamic_routes = array();

    /** @var array */
    private static $_name_routes = array();

    public function __construct(protected RequestInterface $request) {}

    public function build()
    {
        $request = $this->request;
        $matchedRoute = self::matchRoute($request);

        if (!$matchedRoute) {
            throw new RouteNotFoundException($this->request);
        }

        $route = $matchedRoute->route;
        $route_attributes = $route->getAttributes();
        $params = array_slice($matchedRoute->params, 1);

        every($route_attributes, function (RouteAttribute $attribute, $index) use ($route, $params) {
            $name = $attribute->name;
            $value = RouteParser::attributeCast(
                $params[$index] ?? null,
                $attribute->type,
            );

            if ($route->hasOptionalParameter()) {
                $value = substr($value, 0, strlen($value) - 1);
            }

            $this->request->setAttribute($name, $value);
        });

        $response = $route->parseResponse(new Response());
        $result = $route->handle($request, $response);

        EventManager::dispatchEvent(
            Request::EVENT_COMPLETED,
            $this->request
        );

        return $result;
    }

    /**
     * Trim route path
     *
     * @param string $path
     * @return string
     */
    public static function trimPath($path)
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
        return self::$all_routes;
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
        $method = strtoupper($route->getMethod());
        $path = self::trimPath($route->getPath());

        static::$all_routes[] = $route;
        static::bindNamedRoute($route);

        if (!str_contains($path, '{')) {
            static::$static_routes[$method][$path] = $route;
            return $route;
        }

        static::$dynamic_routes[$method][] = [
            'route' => $route,
            'regex' => $route->path()->regexp()
        ];

        return $route;
    }

    /**
     * Find route match
     *
     * @param RequestInterface $request
     * @return RouteMatchResult|null
     */
    public static function matchRoute(RequestInterface $request): ?RouteMatchResult
    {
        $method = strtoupper($request->getMethod());
        $uri = self::trimPath($request->url()->getPath());

        if (isset(static::$static_routes[$method][$uri])) {
            return new RouteMatchResult(
                static::$static_routes[$method][$uri],
                []
            );
        }

        foreach (static::$dynamic_routes[$method] ?? [] as $entry) {
            if (!preg_match("#^{$entry['regex']}$#", $uri, $matches)) {
                continue;
            }

            $params = [];
            foreach ($matches as $key => $value) {
                $params[$key] = $value;
            }

            return new RouteMatchResult(
                $entry['route'],
                $params
            );
        }

        return null;
    }

    /**
     * Bind a named route
     *
     * @param Route $route
     * @return void
     */
    public static function bindNamedRoute(Route $route)
    {
        $name = $route->getName();

        if (!$name) {
            return;
        }

        if (isset(self::$_name_routes[$name])) {
            throw new InvalidArgumentException("Route name '{$name}' is already in use.");
        }

        self::$_name_routes[$name] = $route;
    }

    public static function reset()
    {
        self::$all_routes = array();
        self::$static_routes = array();
        self::$dynamic_routes = array();
        self::$_name_routes = array();
    }
}
