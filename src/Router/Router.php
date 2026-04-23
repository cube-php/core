<?php

namespace Cube\Router;

use InvalidArgumentException;
use Cube\Router\Route;
use Cube\Router\RouteCollection;

class Router
{
    /**
     * Route parent path
     *
     * @var string|null
     */
    protected $_root_path = null;

    /**
     * Middlewares
     *
     * @var array|string|null
     */
    protected array $_root_middlewares = [];

    /**
     * Excluded middlewares
     *
     * @var array
     */
    protected array $_excluded_middlewares = [];

    /**
     * Namespace
     *
     * @var array
     */
    protected $_root_namespace = [];

    /**
     * Parent route
     *
     * @var Router|null
     */
    protected ?Router $_parent = null;

    /**
     * Route name
     *
     * @var array
     */
    protected array $name = array();

    /** @var Route[] */
    protected array $_routes = [];

    /**
     * Constructor
     *
     * @param string $parent_path
     */
    public function __construct($path = null, bool $cors = true, ?self $parent = null)
    {
        $this->_parent = $parent;
        $this->setPath($path);

        if ($parent) {
            $this->setNamespace();
            $this->setMiddleware();
            $this->setExcludedMiddleware();
            $this->name = $parent->name;
        }
    }

    /**
     * Get this router's middleware
     *
     * @return string
     */
    public function getMiddlewares(): array
    {
        return $this->_root_middlewares;
    }

    /**
     * Get this router's excluded middleware
     *
     * @return array
     */
    public function getExcludedMiddlewares(): array
    {
        return $this->_excluded_middlewares;
    }

    /**
     * Get this router's namespace
     *
     * @return array
     */
    public function getNamespace(): ?array
    {
        return $this->_root_namespace;
    }

    /**
     * Get this router's route path
     *
     * @return string|null
     */
    public function getRootPath(): ?string
    {
        return $this->_root_path;
    }

    /**
     * Get routes created by this router context.
     *
     * @return Route[]
     */
    public function getRoutes(): array
    {
        return $this->_routes;
    }

    /**
     * Add a new route on any request method
     * 
     * @param string $path Route path
     * @param string $controller Controller route
     * 
     * @return Route
     */
    public function any($path, $controller)
    {
        return $this->on(null, $path, $controller);
    }

    /**
     * Add a new route on 'GET' request method
     * 
     * @param string $path Route path
     * @param string $controller Controller route
     * 
     * @return Route
     */
    public function get($path, $controller)
    {
        return $this->on('get', $path, $controller);
    }

    /**
     * Add a new route on 'DELETE' request method
     * 
     * @param string $path Route path
     * @param string $controller Controller route
     * 
     * @return Route
     */
    public function delete($path, $controller)
    {
        return $this->on('delete', $path, $controller);
    }

    /**
     * Add a new route group
     * 
     * @param string|null $parent Parent path
     * @param array $options Group Options
     * @param callable $fn Callback function
     * 
     * @return RouterGroup
     */
    public function group(?string $path = null)
    {
        $router = new RouterGroup(
            $path,
            true,
            $this
        );

        return $router;
    }

    /**
     * Set group
     *
     * @param string $name
     * @return $this
     */
    public function name(string $name)
    {
        $this->name[] = $name;
        return $this;
    }

    /**
     * Exclude middlewares from routes in this router context.
     *
     * @param string|array $middleware
     * @return self
     */
    public function withoutMiddleware($middleware)
    {
        $this->setExcludedMiddleware($middleware);
        return $this;
    }

    /**
     * Add a new route on 'POST' request method
     * 
     * @param string $path Route path
     * @param string $controller Controller route
     * 
     * @return Route
     */
    public function post($path, $controller)
    {
        return $this->on('post', $path, $controller);
    }

    /**
     * Add a new route on 'PATCH' request method
     * 
     * @param string $path Route path
     * @param string $controller Controller route
     * 
     * @return Route
     */
    public function patch($path, $controller)
    {
        return $this->on('patch', $path, $controller);
    }

    /**
     * Add a new route on 'POST' request method
     * 
     * @param string[] $methods Request methods
     * @param string $path Route path
     * @param string $controller Controller route
     * 
     * @return Route
     */
    public function map($methods, $path, $controller)
    {
        if (!is_array($methods)) {
            throw new InvalidArgumentException('Router::map() $method should be an array');
        }

        array_walk($methods, function ($method) use ($path, $controller) {
            $this->on($method, $path, $controller);
        });
    }

    /**
     * Add new route to router
     * 
     * @param string $method Request method name
     * @param string $path Route path
     * @param string $controller Controller route
     * 
     * @return Route
     */
    public function on($method, $path, $controller)
    {
        $root_path = $this->_root_path;
        $root_middlewares = $this->_root_middlewares;
        $root_excluded_middlewares = $this->_excluded_middlewares;
        $root_namespace = $this->_root_namespace;

        $route_path = $root_path ? $root_path . $path : $path;
        $route = new Route(
            $method,
            $route_path,
            $controller,
            $this->name
        );

        if ($root_middlewares) {
            $route->use($root_middlewares);
        }

        if ($root_excluded_middlewares) {
            $route->withoutMiddleware($root_excluded_middlewares);
        }

        if ($root_namespace) {
            $route->setNamespace($root_namespace);
        }

        $route = RouteCollection::attachRoute($route);
        $this->_routes[] = $route;

        return $route;
    }

    /**
     * View
     *
     * @param string $path
     * @param string $template
     * @return Route
     */
    public function view($path, $template): Route
    {
        return $this->any($path, Route::VIEW_PREFIX . $template);
    }

    /**
     * Set router's base middlewares
     *
     * @param string|array $middlewares
     * @return void
     */
    protected function setMiddleware($middlewares = null)
    {
        $parent = $this->_parent;
        $parent_middlewares = $parent ? $parent->getMiddlewares() : [];

        if (!$this->_root_middlewares && !$parent_middlewares && !$middlewares) {
            return;
        }

        $context = $this->_root_middlewares ?: (
            is_array($parent_middlewares) ? $parent_middlewares : [$parent_middlewares]
        );

        if (!$middlewares) {
            $this->_root_middlewares = $context;
            return;
        }

        $scoped_middlewares = is_array($middlewares) ? $middlewares : [$middlewares];
        $this->_root_middlewares = array_merge($context, $scoped_middlewares);
    }

    /**
     * Set router's excluded middlewares
     *
     * @param string|array|null $middlewares
     * @return void
     */
    protected function setExcludedMiddleware($middlewares = null)
    {
        $parent = $this->_parent;
        $parent_middlewares = $parent ? $parent->getExcludedMiddlewares() : [];

        if (!$this->_excluded_middlewares && !$parent_middlewares && !$middlewares) {
            return;
        }

        $context = $this->_excluded_middlewares ?: (
            is_array($parent_middlewares) ? $parent_middlewares : [$parent_middlewares]
        );

        if (!$middlewares) {
            $this->_excluded_middlewares = $context;
            return;
        }

        $scope = is_array($middlewares) ? $middlewares : [$middlewares];
        $this->_excluded_middlewares = array_merge($context, $scope);
    }

    /**
     * Set router's base namespace
     *
     * @param string|null $namespace
     * @return void
     */
    protected function setNamespace(?string $namespace = null)
    {
        $parent = $this->_parent;

        if (!$parent && !$namespace) {
            return;
        }

        $parent_namespace = $parent ? $parent->getNamespace() : array();

        if ($namespace) {
            $parent_namespace[] = $namespace;
        }

        $this->_root_namespace = $parent_namespace;
    }

    /**
     * Set route's base path
     *
     * @param string|null $path
     * @return void
     */
    private function setPath(?string $path = null)
    {
        $parent = $this->_parent;
        $this->_root_path = $parent ? $parent->getRootPath() . $path : $path;
    }
}
