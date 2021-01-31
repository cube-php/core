<?php

namespace Cube\Http;

use InvalidArgumentException;
use Cube\Interfaces\RequestInterface;

use Cube\Http\Server;
use Cube\Http\Headers;
use Cube\Http\Session;
use Cube\Http\Uri;

use Cube\Misc\FilesParser;
use Cube\Misc\Inputs;
use Cube\Misc\Input;
use Cube\App;

class Request implements RequestInterface
{

    const MIDDLEWARE_ARGS_DELIMETER = ':';

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
     * Input
     *
     * @var Input
     */
    private static $_input;

    /**
     * Request body
     *
     * @var mixed
     */
    private $_body;

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
        $this->_body = file_get_contents('php://input');
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

        if(!$ware) {
            throw new InvalidArgumentException
                ('Custom method "'. $method .'" not assigned');
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
    public function getBody($fields = null, $as_input = true)
    {
        $body = trim($this->_body);
        $fields_key = is_array($fields) 
                        ? $fields
                        : ($fields ? explode(',', $fields) : []);
        
        if(!$body && !count($fields_key)) {
            return null;
        }

        if(!$fields && !count($fields_key)) {
            return $body;
        }

        if(!$as_input) {
            $decoded_data = json_decode($body, true);
            return array_values($decoded_data);
        }

        $returns = [];
        $fields = array_map(function ($field) {
            return trim($field);
        }, $fields_key);

        $is_json_body = in_array(substr($body, 0, 1), ['{', '[']);
        $is_json_body ? $data = json_decode($body) : parse_str($body, $data);

        $inputs = new Inputs($data ? http_build_query($data) : '');

        if(!count($fields)) {
            return $inputs;
        }

        foreach($fields as $field) {
            $returns[] = $inputs->get($field);
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
        static::$_headers ??= new Headers();
        return static::$_headers;
    }

    /**
     * Return request server variables
     *
     * @return Server;
     */
    public function getServer()
    {
        static::$_server ??= new Server();
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

        if(!$index) return $parsed_files;

        $indexes = explode('.', $index);
        $trimmed_indexes = array_map('trim', $indexes);

        foreach($trimmed_indexes as $file_index)
        {
            if(is_null($parsed_files)) return null;
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
     * @param string $name Input name
     * @param string $defaults Default value if input isn't found
     * @return Input|Input[]
     */
    public function input($name, $defaults = null)
    {
        $names = explode(',', $name);
        
        if(count($names) == 1) {
            $raw_value = $this->inputs()->get($name);
            $input = is_array($raw_value) ? $raw_value : ($raw_value->getValue() ?? $defaults);
            return new Input($input, $name);
        }

        $names = array_map('trim', $names);
        $defaults_vars = explode(',', $defaults);
        $single_default = count($defaults_vars) == 1;
        $inputs = [];

        foreach($names as $index => $rname) {
            $default = $single_default ? $defaults : $defaults_vars[$index];
            $raw_value = $this->inputs()->get($rname);
            $input = is_array($raw_value) ? $raw_value : ($raw_value->getValue() ?? $defaults);
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
        if(static::$_input) {
            return static::$_input;
        }

        $content = http_build_query($_POST);
        static::$_input = new Inputs($content);
        return static::$_input;
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

        if(in_array(strtolower($name), $reserved_method_names)) {
            throw new InvalidArgumentException
                ('The specifed method name is a reserved method name');
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
        if(static::$_url) {
            return static::$_url;
        }

        $scheme = $this->getServer()->isHTTPs() ? 'https' : 'http';
        $host = $this->getServer()->get('http_host');
        $uri = $this->getServer()->get('request_uri');

        static::$_url = new Uri($scheme . '://' . $host . $uri);
        return static::$_url;
    }

    /**
     * Undocumented function
     *
     * @param string[] $middleware Middleware name
     *
     * @return self
     * 
     * @throws \InvalidArgumentException
     */
    public function useMiddleware($middlewares)
    {
        if(!count($middlewares)) {
            return $this;
        }

        $wares = $this->getMiddlewareResolved();
        $result = $this;
        $stopped = false;

        foreach($middlewares as $middleware) {

            $vars = explode(':', $middleware);

            $key = $vars[0];
            $args = $vars[1] ?? null;
            $class = $wares[$key] ?? null;

            if(!$class) {
                throw new InvalidArgumentException ('Middleware "'.$key.'" is not assigned');
            }

            $args_value = $args ? explode(',', $args) : null;
            $result = call_user_func_array([new $class, 'trigger'], [$result, $args_value]);

            if($result instanceof Response) {
                $stopped = true;
                break;
            }
        }

        if($stopped) {
            return null;
        }

        return $result;
    }

    /**
     * Get resolved middlewares
     *
     * @return array
     */
    protected function getMiddlewareResolved()
    {
        if(static::$_resolved_middlewares) {
            return static::$_resolved_middlewares;
        }

        $wares = App::getConfigByFile('middleware');
        
        if(!$wares) {
            return false;
        }

        array_walk($wares, function ($class, $key) {
            if(strpos($key, self::MIDDLEWARE_ARGS_DELIMETER)) {
                throw new InvalidArgumentException('Middleware keys must not contain ' . self::MIDDLEWARE_ARGS_DELIMETER);
            }
        });

        static::$_resolved_middlewares = $wares;
        return $wares;
    }
}