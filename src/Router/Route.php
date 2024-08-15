<?php

namespace Cube\Router;

use Cube\App\App;
use Closure;
use Cube\Exceptions\AppException;
use InvalidArgumentException;

use Cube\Router\RouteParser;
use Cube\Http\Request;
use Cube\Http\Response;
use Cube\Http\AnonController;
use Cube\Http\Model;
use Cube\Misc\ModelCollection;
use Cube\Misc\PaginatedModelQueryResult;
use Stringable;

class Route
{
    public const ROUTE_NAME_IMPODER = '.';

    /**
     * Prefix to register a view rather than a controller for route
     *
     * @var string
     */
    public const VIEW_PREFIX = '@';

    /**
     * Route's Controllers namespace
     * 
     * @var array
     */
    private const CONTROLLER_NAMESPACE = ['App', 'Controllers'];

    /**
     * Route's 
     * 
     * @var string
     */
    private const NAMESPACE_SEPARATOR = '\\';

    /**
     * Route method
     * 
     * @var string[]
     */
    private $_method = array();

    /**
     * Route's name
     *
     * @var string
     */
    private $_name;

    /**
     * Route path
     * 
     * @var string
     */
    private $_path;

    /**
     * Route controller
     * 
     * @var string|Closure
     */
    private $_controller = array();

    /**
     * Route's name space
     *
     * @var array
     */
    private $_namespace = self::CONTROLLER_NAMESPACE;

    /**
     * Route attributes
     * 
     * @var string[]
     */
    private $_attributes = [];

    /**
     * Set if the controller is a callable
     *
     * @var boolean
     */
    private $_is_callble_controller = false;

    /**
     * Has optional parameter
     *
     * @var boolean
     */
    private $_has_optional_parameter = false;

    /**
     * Middlewares
     * 
     * @var string[]
     */
    private $_middlewares = [];

    /**
     * CORS status
     *
     * @var boolean
     */
    private $_enable_cors = true;

    private $_params_list = [];

    /**
     * Route's parents' names
     *
     * @var array
     */
    private array $parent_names = [];

    /**
     * Class constructor
     * 
     * @param string|string[] $method
     * @param string $path
     * @param string $controller
     * @param array|null $parent_names
     */
    public function __construct($method, $path, $controller, ?array $parent_names = null)
    {
        $this->setMethod(strtolower((string) $method));
        $this->setPath($path);
        $this->setController($controller);
        $this->parent_names = $parent_names;
    }

    /**
     * Get route as string
     *
     * @return string
     */
    public function __toString()
    {
        return '[' . $this->_method . '] ' . $this->_path . ' ' . $this->getControllerName();
    }

    /**
     * Get route controller name
     *
     * @return string
     */
    public function getControllerName()
    {
        $controller = $this->_controller;

        if (is_array($controller)) {
            return implode('.', $controller);
        }

        if (is_callable($controller)) {
            return 'Closure';
        }

        return $controller;
    }

    public function getParams()
    {
        return $this->_params_list;
    }

    /**
     * Add specified middlewares to request
     *
     * @param Request $request
     * @return Request|null
     */
    public function engageMiddleware(Request $request)
    {
        return $request->useMiddleware($this->_middlewares);
    }

    /**
     * Set namespace
     *
     * @param string $namespace
     * @return self
     */
    public function namespace(string $namespace)
    {
        return $this->setNamespace($namespace);
    }

    /**
     * Set route controller
     * 
     * @param string $controller Controller pattern
     * 
     * <<Controller pattern>>
     * If your controller class is HomeController
     * And you want route to focus on HomeController::index() method
     * Set controller path to
     * 
     * "HomeController.index"
     * 
     * <</Controller pattern>>
     * 
     * @return self
     * 
     * @throws \InvalidArgumentException if $controller is not callable
     */
    public function setController($controller)
    {
        $this->_controller = $controller;
        return $this;
    }

    /**
     * Set route path
     * 
     * @param string $path Route path
     * 
     * @return self
     */
    public function setPath($path)
    {
        preg_match_all('/(\{(.*?)\})/i', $path, $matches);
        [$params_list] = $matches;

        every($params_list, function ($param) {
            $raw_param = str_replace(['{', '}'], '', $param);
            $name = str_contains($param, ':') ? explode(':', $raw_param)[1] : $raw_param;

            $this->_params_list[$param] = $name;
        });

        $this->_path = $path;
        return $this;
    }

    /**
     * Set route methods
     * 
     * @param string|string[] $method String or array of HTTP request methods
     * 
     * @return self
     */
    public function setMethod($method)
    {
        $this->_method = $method;
        return $this;
    }

    /**
     * Set route namespace
     *
     * @param array|string $namespace
     * @return self
     */
    public function setNamespace($namespace)
    {
        if (is_string($namespace)) {
            $this->_namespace[] = $namespace;
        }

        if (is_array($namespace)) {
            $this->_namespace = array_merge($this->_namespace, $namespace);
        }

        return $this;
    }

    /**
     * Set whether or not to allow CORS
     *
     * @param boolean $cors
     * @return self
     */
    public function setEnableCors(bool $cors)
    {
        $this->_enable_cors = $cors;
        return $this;
    }

    /**
     * Set attributes found in route path
     * 
     * @param string $name Attribute field name
     * @param string $type Attribute field type
     * 
     * @return void
     */
    public function setAttribute($name, $type = null)
    {
        $this->_attributes[] = (object) array(
            'name' => $name,
            'type' => $type
        );

        return $this;
    }

    /**
     * Set if route has optional parameter
     *
     * @param string $val
     * @return self
     */
    public function setHasOptionalParameter(bool $val)
    {
        $this->_has_optional_parameter = $val;
        return $this;
    }

    /**
     * Return all attributes in route
     * 
     * @return array[]
     */
    public function getAttributes()
    {
        return $this->_attributes;
    }

    /**
     * Returns the route's request method
     * 
     * @return string Request method
     */
    public function getMethod()
    {
        return $this->_method;
    }

    /**
     * Returns the routes controller object
     * 
     * @return object
     */
    public function getController()
    {
        return $this->_controller;
    }

    /**
     * Check if route has an optional parameter
     *
     * @return boolean
     */
    public function hasOptionalParameter()
    {
        return $this->_has_optional_parameter;
    }

    /**
     * Handle request
     *
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request)
    {
        $embed_request = App::getConfig('view', 'embed_request') ?? false;
        $response = new Response();

        if ($embed_request) {
            $response->req = $request;
        }

        #Check if route is registered with a view
        $view_file_path = $this->_isReturnView();
        if ($view_file_path) {
            return $response->view($view_file_path);
        }

        #Parse controller
        $this->parseController();

        #Parse anonymous controller
        if ($this->_is_callble_controller) {
            $controller = Closure::bind(
                $this->_controller,
                new AnonController(),
                AnonController::class
            );

            return $this->_analyzeControllerResult($controller, $request, $response);
        }

        $class = $this->_controller['class_name'];
        $method = $this->_controller['method_name'];

        /**
         * Create controller instance
         * This is to be able to invoke middleware assigned in __construct
         */
        $controller = new $class($request, $response);
        $request = $request->useMiddleware(
            $controller->getMiddlewares()
        );

        if ($request instanceof Response) {
            return $request;
        }

        $request = $this->engageMiddleware($request);

        if ($request instanceof Response) {
            return $request;
        }

        $middleware_fn_name = '__middleware';

        if (is_callable([$controller, $middleware_fn_name])) {
            $request = call_user_func_array(
                [$controller, $middleware_fn_name],
                [$request, $response]
            );

            if ($request instanceof Response) {
                return $request;
            }
        }

        if (!is_callable([$controller, $method])) {
            throw new InvalidArgumentException("{$class}::{$method}() on route \"{$this->getPath()}\" is not a valid callable method");
        }

        return $this->_analyzeControllerResult(
            [$controller, $method],
            $request,
            $response
        );
    }

    /**
     * Returns the specified route path
     * 
     * @return string
     */
    public function getPath()
    {
        return $this->_path;
    }

    /**
     * Route's name
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->_name;
    }

    /**
     * Set routes name
     *
     * @param string $name
     * @return self
     */
    public function name(string $name): self
    {
        $names = $this->parent_names;
        $names[] = $name;

        $this->_name = implode(self::ROUTE_NAME_IMPODER, $names);
        RouteCollection::bindNamedRoute($this);
        return $this;
    }

    /**
     * Custom response values/header
     *
     * @param Response $response
     * @return Response
     */
    public function parseResponse(Response $response)
    {
        $cors_method = $this->_enable_cors ? 'enableCors' : 'disableCors';
        $response = call_user_func([$response, $cors_method]);
        return $response;
    }

    /**
     * Return the regexp-fied route path
     * 
     * @return RouteParser
     */
    public function path()
    {
        return new RouteParser($this);
    }

    /**
     * Set Route middlewares
     *
     * @param string|array $wares Middlewares
     * @return self
     */
    public function use($wares)
    {
        if (is_array($wares) && count($wares)) {
            foreach ($wares as $ware) {
                $this->_middlewares[] = (is_string($ware) && class_exists($ware))
                    ? new $ware() : $ware;
            }
            return $this;
        }

        $this->_middlewares[] = $wares;
        return $this;
    }

    /**
     * Router to json
     *
     * @return array
     */
    public function toJSON(): array
    {
        return array(
            'namespace' => array_slice($this->_namespace, 2),
            'controller' => $this->getController(),
            'middlewares' => $this->getParsedMiddlewares(),
            'method' => $this->getMethod(),
            'path' => $this->getPath(),
            'name' => $this->getName()
        );
    }

    /**
     * Parsed middlewares
     *
     * @return array
     */
    private function getParsedMiddlewares(): array
    {
        $middlewares = array();
        every($this->_middlewares, function ($middleware) use (&$middlewares) {
            $middlewares[] = is_object($middleware) ? $middleware::class : $middleware;
        });

        return $middlewares;
    }

    /**
     * Analyze route's controller
     *
     * @param string|array $controller
     * @param Request $request
     * @param Response $response
     * @return string|Response result
     */
    private function _analyzeControllerResult($controller, Request $request, Response $response)
    {
        $result = call_user_func_array($controller, [$request, $response]);

        if ($result instanceof Response) {
            return $result;
        }

        if ($result instanceof Model) {
            return $response->json($result->data());
        }

        if ($result instanceof ModelCollection) {
            return $response->json(
                model2array($result)
            );
        }

        if ($result instanceof PaginatedModelQueryResult) {
            return $response->json(
                $result->toResponse()
            );
        }

        if (is_string($result) || $result instanceof Stringable) {
            return $response->write($result);
        }

        if (is_array($result)) {
            return $response->json($result);
        }

        if (!$result) {
            return $response->write('');
        }

        throw new AppException('Response not returned');
    }

    /**
     * Return value if route is registered to return only view
     *
     * @return boolean|string
     */
    private function _isReturnView()
    {
        $controller = $this->_controller;

        if (!is_string($controller)) {
            return false;
        }

        $first_value = substr($controller, 0, 1);

        if ($first_value != self::VIEW_PREFIX) {
            return false;
        }

        return substr($controller, 1);
    }

    /**
     * Parse controller
     *
     * @return bool
     */
    private function parseController()
    {
        $controller = $this->_controller;

        if (is_callable($controller)) {
            $this->_is_callble_controller = true;
            $this->_controller = $controller;
            return true;
        }

        if (is_array($controller)) {
            return $controller;
        }

        $controller_vars = explode('.', $controller);
        $controller_vars_count = count($controller_vars);

        if ($controller_vars_count < 2 || $controller_vars_count > 2) {
            throw new InvalidArgumentException('Controller should be passed as "ClassName.methodName"');
        }

        $namespace = implode(self::NAMESPACE_SEPARATOR, $this->_namespace);
        $controller_class_name = $namespace . self::NAMESPACE_SEPARATOR . $controller_vars[0];
        $controller_method_name = $controller_vars[1];

        $this->_controller = array(
            'class_name' => $controller_class_name,
            'method_name' => $controller_method_name
        );

        return true;
    }
}
