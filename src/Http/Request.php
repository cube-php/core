<?php

namespace Cube\Http;

use InvalidArgumentException;
use Cube\Interfaces\RequestInterface;

use Cube\Http\Server;
use Cube\Http\Headers;
use Cube\Http\Uri;

use Cube\Misc\FilesParser;
use Cube\Misc\Inputs;
use Cube\Misc\Input;
use Cube\App\App;
use Cube\Interfaces\MiddlewareInterface;
use Cube\Misc\RequestValidator;

class Request implements RequestInterface
{
    private static ?self $running_instance = null;

    /**
     * Request completed event
     * 
     * @var string
     */
    public const EVENT_COMPLETED = 'onRequestCompleted';

    /**
     * Middleware delimeter
     */
    public const MIDDLEWARE_ARGS_DELIMETER = ':';

    /**
     * Request parameters
     * 
     * @var array
     */
    private $attributes = array();

    /**
     * Middlewares
     * 
     * @var string[]
     */
    public $_wares = array();

    /**
     * Server
     * 
     * @var Server
     */
    private static $_server;

    /**
     * Header
     * 
     * @var Headers
     */
    private static $_headers;

    /**
     * Url
     *
     * @var Uri
     */
    private static $_url;

    /**
     * Request body
     *
     * @var mixed
     */
    private $_body;

    /**
     * Input
     *
     * @var Inputs
     */
    private $_processed_body;

    /**
     * All resolved middlewares
     *
     * @var array|null
     */
    private static $_resolved_middlewares = null;

    /**
     * Class constructor
     * 
     */
    public function __construct()
    {
        self::$running_instance = $this;
        $this->parseBody();
    }

    /**
     * Call middlewares
     * 
     * @param string $method Method name
     * @param string[] $args Method arguments
     * 
     * @return callable
     */
    public function __call($method, $args)
    {
        $ware = array_key_exists($method, $this->_wares);

        if (!$ware) {
            throw new InvalidArgumentException('Custom method "' . $method . '" not assigned');
        }

        return call_user_func($this->_wares[$method], $args);
    }

    /**
     * Getter
     * 
     * @param string $name Getter name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->getAttribute($name);
    }

    /**
     * Get request body
     *
     * @param array|string|null $fields Fields to retrieve if return content is Input
     * @param boolean $as_input Set whether body content should be wrapped as an input
     * @return Input[]|string
     */
    public function getBody($fields = null)
    {
        $body = trim($this->_body);
        $fields_key = is_array($fields)
            ? $fields
            : ($fields ? explode(',', $fields) : []);

        if (!$body && !count($fields_key)) {
            return null;
        }

        if (!$fields && !count($fields_key)) {
            return $body;
        }

        $returns = [];
        $fields = array_map('trim', $fields_key);

        if (!count($fields)) {
            return $this->_processed_body;
        }

        foreach ($fields as $field) {
            $returns[] = $this->_processed_body->get($field);
        }

        return $returns;
    }

    /**
     * Return parsed request body
     *
     * @return string JSON parsed string
     */
    public function getParsedBody()
    {
        return json_decode($this->getBody());
    }

    /**
     * Return request headers
     *
     * @return Headers
     */
    public function getHeaders()
    {
        if (static::$_headers) {
            return static::$_headers;
        }

        static::$_headers = new Headers();
        return static::$_headers;
    }

    /**
     * Return request server variables
     *
     * @return Server;
     */
    public function getServer()
    {
        if (static::$_server) {
            return static::$_server;
        }

        static::$_server = new Server();
        return static::$_server;
    }

    /**
     * Get uploaded files
     *
     * @param string $index Uploaded file name path
     *
     * @return UploadedFile|array
     */
    public function getUploadedFiles($index = null)
    {
        $parser = new FilesParser($_FILES);
        $parsed_files = $parser->parse();

        if (!$index) return $parsed_files;

        $indexes = explode('.', $index);
        $trimmed_indexes = array_map('trim', $indexes);

        foreach ($trimmed_indexes as $file_index) {
            if (is_null($parsed_files)) return null;
            $parsed_files = $parsed_files[$file_index] ?? null;
        }

        return $parsed_files;
    }

    /**
     * Get client request method
     * 
     * @return string
     */
    public function getMethod()
    {
        return strtolower($this->getServer()->get('request_method'));
    }

    /**
     * Get request attribute
     *
     * @param string $name Attribute name
     * @param mixed $default_value Otherwise value to return if attribute is not found
     * 
     * @return mixed
     */
    public function getAttribute($name, $default_value = null)
    {
        return $this->attributes[$name] ?? $default_value;
    }

    /**
     * Check if a custom method exists on request
     *
     * @param string $name
     * @return boolean
     */
    public function hasCustomMethod(string $name): bool
    {
        return in_array($name, $this->_wares);
    }

    /**
     * Check if input field exists
     *
     * @param string $name Input name
     * 
     * @return bool
     */
    public function hasInput($name)
    {
        return !!$this->input($name);
    }

    /**
     * Get input
     *
     * @param string|array $name Input name
     * @param string $defaults Default value if input isn't found
     * @return Input|Input[]
     */
    public function input(string | array $name, string $defaults = '')
    {
        $names = is_string($name) ? explode(',', $name) : $name;

        if (count($names) == 1) {
            $raw_value = $this->inputs()->get($name);
            $input = is_array($raw_value) ? $raw_value : ($raw_value->getValue() ?? $defaults);
            return new Input($input, $name);
        }

        $names = array_map('trim', $names);
        $defaults_vars = explode(',', $defaults);
        $single_default = count($defaults_vars) == 1;
        $inputs = [];

        foreach ($names as $index => $rname) {
            $default = $single_default ? $defaults : $defaults_vars[$index];
            $raw_value = $this->inputs()->get($rname);
            $input = is_array($raw_value) ? $raw_value : ($raw_value->getValue() ?? $default);
            $inputs[] = new Input($input, $rname);
        }

        return $inputs;
    }

    /**
     * Get all inputs sent in the request
     * 
     * @return Inputs
     */
    public function inputs()
    {
        return $this->_processed_body;
    }

    /**
     * Add request attributes to space
     * 
     * @param string $name Attribute field name
     * @param mixed[] $value Attribute field value
     * 
     * @return self
     */
    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    /**
     * Set custom method
     *
     * @param string $name Method name
     * @param Closure $fn Callable
     * @return self
     */
    public function setCustomMethod($name, $fn)
    {
        $reserved_method_names = array_map('strtolower', get_class_methods($this));

        if (in_array(strtolower($name), $reserved_method_names)) {
            throw new InvalidArgumentException('The specifed method name is a reserved method name');
        }

        $this->_wares[$name] = $fn;
        return $this;
    }

    /**
     * Get this request url
     * 
     * @return \Cube\Http\Uri
     */
    public function url()
    {
        if (static::$_url) {
            return static::$_url;
        }

        $scheme = $this->getServer()->isHTTPs() ? 'https' : 'http';
        $host = $this->getServer()->get('http_host');
        $uri = $this->getServer()->get('request_uri');

        static::$_url = new Uri($scheme . '://' . $host . $uri);
        return static::$_url;
    }

    /**
     * Use middleware
     *
     * @param string[]|string $middleware Middleware name
     *
     * @return self
     * 
     * @throws \InvalidArgumentException
     */
    public function useMiddleware($middleware_list)
    {
        $middlewares = is_array($middleware_list) ? $middleware_list : [$middleware_list];

        if (!count($middlewares)) {
            return $this;
        }

        $wares = $this->getMiddlewareResolved();
        $result = $this;
        $stopped = false;

        foreach ($middlewares as $middleware) {

            if (is_object($middleware)) {
                if (!is_a($middleware, MiddlewareInterface::class)) {
                    throw new InvalidArgumentException(
                        sprintf('"%s" is not a middleware', $middleware::class)
                    );
                }

                $result = $middleware->trigger($this);
            }

            if (is_callable($middleware)) {
                $result = $middleware($result);
            }

            if (is_string($middleware)) {
                $vars = explode(':', $middleware);

                $key = $vars[0];
                $args = $vars[1] ?? null;
                $class = $wares[$key] ?? null;

                if (!$class) {
                    throw new InvalidArgumentException('Middleware "' . $key . '" is not assigned');
                }

                $args_value = $args ? explode(',', $args) : null;
                $result = call_user_func_array([new $class, 'trigger'], [$result, $args_value]);
            }

            if ($result instanceof Response) {
                $stopped = true;
                break;
            }
        }

        if ($stopped) {
            return $result;
        }

        return $result;
    }

    /**
     * Validate Input
     *
     * @param array $rules
     * @return RequestValidator
     */
    public function validate(array $rules)
    {
        $validator = new RequestValidator($this);
        $validator->addRules($rules);
        return $validator;
    }

    /**
     * Get running request instance
     *
     * @return self
     */
    public static function getRunningInstance(): self
    {
        return self::$running_instance ??= new self();
    }

    /**
     * Get resolved middlewares
     *
     * @return array
     */
    protected function getMiddlewareResolved()
    {
        if (static::$_resolved_middlewares) {
            return static::$_resolved_middlewares;
        }

        $wares = App::getRunningInstance()->getConfig('middleware');

        if (!$wares) {
            return false;
        }

        array_walk($wares, function ($class, $key) {
            if (strpos($key, self::MIDDLEWARE_ARGS_DELIMETER)) {
                throw new InvalidArgumentException('Middleware keys must not contain ' . self::MIDDLEWARE_ARGS_DELIMETER);
            }
        });

        static::$_resolved_middlewares = $wares;
        return $wares;
    }

    /**
     * Parse request body
     *
     * @return void
     */
    private function parseBody()
    {
        $body_content = file_get_contents('php://input');
        $body = (!$body_content && count($_POST))
            ? json_encode($_POST)
            : $body_content;

        $this->_body = $body;
        $is_json = str($body)->isJson();

        $this->_processed_body = new Inputs(
            $is_json
                ? http_build_query(json_decode($body, true))
                : $body
        );
    }
}
