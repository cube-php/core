<?php

namespace Cube\Http;

use InvalidArgumentException;
use Cube\Interfaces\RequestInterface;

use Cube\Http\Headers;
use Cube\Http\Uri;

use Cube\Misc\FilesParser;
use Cube\Misc\Inputs;
use Cube\Misc\Input;
use Cube\App\App;
use Cube\Interfaces\MiddlewareInterface;
use Cube\Misc\Collection;
use Cube\Misc\RequestValidator;
use Cube\Http\UploadedFile;

class Request implements RequestInterface
{
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
     * Url
     *
     * @var Uri
     */
    private ?Uri $uri = null;

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
    private array $resolved_middlewares = [];

    /**
     * Middlewares that have been called
     *
     * @var array
     */
    private array $called_middlewares = [];

    /**
     * parsed uploaded files
     *
     * @var UploadedFile[]
     */
    private array $uploaded_files = array();

    /**
     * Create a new request
     *
     * @param Collection $server
     * @param Collection $header
     * @param Collection $cookie
     * @param Collection|null $get
     * @param Collection|null $post
     * @param Collection|null $files
     * @param Collection|null $tmpfiles
     * @param string $content
     */
    public function __construct(
        protected Collection $server,
        protected Collection $header,
        protected Collection $cookie,
        protected ?Collection $get = null,
        protected ?Collection $post = null,
        protected ?Collection $files = null,
        protected ?Collection $tmpfiles = null,
        protected string $content = ''
    ) {
        $this->parseBody();
        $this->updateHistory();
        $this->uploaded_files =  (new FilesParser(
            $this->files->getArrayCopy()
        ))->parse();
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
        if (property_exists($this, $method)) {
            return $this->{$method};
        }

        $ware = array_key_exists($method, $this->_wares);

        if (!$ware) {
            return null;
        }

        return $this->_wares[$method];
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
     * Get HTTP Cookies
     *
     * @return Collection
     */
    public function getCookies(): Collection
    {
        return $this->cookie;
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
     * @return Collection
     */
    public function getHeaders(): Collection
    {
        return $this->header;
    }

    /**
     * Return request server variables
     *
     * @return Collection;
     */
    public function getServer(): Collection
    {
        return $this->server;
    }

    /**
     * Get uploaded files
     *
     * @param string $index Uploaded file name path
     *
     * @return UploadedFile|array
     */
    public function getUploadedFiles(string $index = '')
    {
        $parsed_files = $this->uploaded_files;

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
     * Get list of used middlewares
     *
     * @return array
     */
    public function getMiddlewares(): array
    {
        return $this->called_middlewares;
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

        $this->_wares[$name] = $fn();
        return $this;
    }

    /**
     * Get this request url
     * 
     * @return \Cube\Http\Uri
     */
    public function url()
    {
        if ($this->uri) {
            return $this->uri;
        }

        $is_https = ((string) $this->get->get('https') === 'on');
        $scheme = $is_https ? 'https' : 'http';
        $host = $this->getServer()->get('http_host');
        $uri = $this->getServer()->get('request_uri');

        if ($this->get->count()) {
            $uri .= '?' . http_build_query($this->get->getArrayCopy());
        }

        $this->uri = new Uri($scheme . '://' . $host . $uri);
        return $this->uri;
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

        foreach ($middlewares as $middleware) {

            if (is_object($middleware)) {
                if (!is_a($middleware, MiddlewareInterface::class)) {
                    throw new InvalidArgumentException(
                        sprintf('"%s" is not a middleware', $middleware::class)
                    );
                }

                $this->called_middlewares[] = $middleware::class;
                $result = $middleware->trigger($result);
            }

            if (is_callable($middleware)) {
                $this->called_middlewares[] = $middleware;
                $result = $middleware($result);
            }

            if (is_string($middleware)) {
                $vars = explode(':', $middleware);

                $key = $vars[0];
                $args = $vars[1] ?? null;
                $class = $wares[$key] ?? null;
                $this->called_middlewares[] = $class;

                if (!$class) {
                    throw new InvalidArgumentException('Middleware "' . $key . '" is not assigned');
                }

                $args_value = $args ? explode(',', $args) : null;
                $result = call_user_func_array([new $class, 'trigger'], [$result, $args_value]);
            }

            if ($result instanceof Response) {
                break;
            }
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
     * Get resolved middlewares
     *
     * @return array
     */
    protected function getMiddlewareResolved()
    {
        if ($this->resolved_middlewares) {
            return $this->resolved_middlewares;
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

        $this->resolved_middlewares = $wares;
        return $wares;
    }

    /**
     * Update request url history
     *
     * @return void
     */
    private function updateHistory(): void
    {
        $history = Session::get('_cubeHttpUrlHistory_') ?? [];
        $last_url = array_get_last($history) ?? '';

        if ($last_url === $this->url()->getUrl()) {
            return;
        }

        $history[] = $this->url()->getUrl();
        Session::set('cubeHttpUrlHistory', $history);
    }

    /**
     * Parse request body
     *
     * @return void
     */
    private function parseBody()
    {
        if (strtoupper($this->getMethod()) === 'GET') {
            return $this->_body = '';
        }

        $content = $this->content;
        $post = $this->post?->all() ?? [];
        $body = (!!count($post)) ? json_encode($post) : $content;

        $this->_body = $body;
        $is_json = str($body)->isJson();

        $inputs = $is_json ? json_decode($body, true) : $body;
        $this->_processed_body = new Inputs($inputs);
    }

    /**
     * Get current request
     *
     * @return self|null
     */
    public static function getCurrentRequest(): ?self
    {
        return Session::get('cubeHttpRequest');
    }

    /**
     * Create new request from globals
     *
     * @return self
     */
    public static function createHttpRequestFromGlobals(): self
    {
        return new self(
            new Collection($_SERVER),
            new Collection(new Headers()),
            new Collection($_COOKIE),
            new Collection($_GET),
            new Collection($_POST),
            new Collection($_FILES),
            null,
            (string) file_get_contents('php://input'),
        );
    }
}
