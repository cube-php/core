<?php

namespace Cube\Http;

use Closure;
use InvalidArgumentException;
use Cube\Interfaces\RequestInterface;

use Cube\Http\Headers;
use Cube\Http\Uri;

use Cube\Misc\FilesParser;
use Cube\Misc\Inputs;
use Cube\Misc\Input;
use Cube\Http\Middleware\MiddlewarePipeline;
use Cube\Http\Session\SessionManager;
use Cube\Http\Session\SessionManagerFactory;
use Cube\Misc\Collection;
use Cube\Misc\RequestValidator;
use Cube\Http\UploadedFile;
use Cube\Http\Session\SessionHandler;
use RuntimeException;

class Request implements RequestInterface
{
    public const EVENT_COMPLETED = 'onRequestCompleted';

    public const MIDDLEWARE_ARGS_DELIMETER = ':';

    private array $attributes = array();

    /** @var string[] */
    public $_wares = array();

    private ?Uri $uri = null;

    private mixed $_body;

    private Inputs $_processed_body;

    private array $called_middlewares = [];

    private ?SessionHandler $session = null;

    private ?SessionManager $session_manager = null;

    /** @var UploadedFile[] */
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
        app()->scoped(
            Response::class,
            fn() => new Response()
        );

        $this->parseBody();
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
     * Start session
     *
     * @return void
     */
    public function startSession()
    {
        if ($this->session) {
            return;
        }

        $this->session_manager = SessionManagerFactory::make();
        $this->session = $this->session_manager->start($this);

        app()->scoped(
            SessionHandler::class,
            fn() => $this->session
        );
    }

    /**
     * Get request body
     *
     * @param array|string|null $fields Fields to retrieve if return content is Input
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
     * @return mixed JSON parsed string
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
     * Get session
     *
     * @return SessionHandler
     */
    public function session(): SessionHandler
    {
        if (!$this->session) {
            $this->startSession();
        }

        return app(SessionHandler::class);
    }

    /**
     * Get session manager
     *
     * @return SessionManager|null
     */
    public function getSessionManager(): ?SessionManager
    {
        return $this->session_manager;
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
        return array_key_exists($name, $this->_wares);
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
            $value = is_array($raw_value) ? $raw_value : $raw_value->getValue();
            $input = $this->inputExists($name) ? $value : $defaults;
            return new Input($input, $name);
        }

        $names = array_map('trim', $names);
        $defaults_vars = explode(',', $defaults);
        $single_default = count($defaults_vars) == 1;
        $inputs = [];

        foreach ($names as $index => $rname) {
            $default = $single_default ? $defaults : $defaults_vars[$index];
            $raw_value = $this->inputs()->get($rname);
            $value = is_array($raw_value) ? $raw_value : $raw_value->getValue();
            $input = $this->inputExists($rname) ? $value : $default;
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

    private function inputExists(string $name): bool
    {
        $value = $this->inputs()->all();

        foreach (explode('.', trim($name)) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return false;
            }

            $value = $value[$part];
        }

        return true;
    }

    /**
     * Add request attributes to space
     * 
     * @param string $name Attribute field name
     * @param mixed[] $value Attribute field value
     * 
     * @return self|Response
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
     * @param callable $fn Callable
     * @return self
     */
    public function setCustomMethod(string $name, callable $fn): self
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
     * @param mixed $middleware_list Middleware name, callable, object, or list
     *
     * @return self
     * 
     * @throws \InvalidArgumentException
     */
    public function useMiddleware(mixed $middleware_list)
    {
        if (is_array($middleware_list) && !count($middleware_list)) {
            return $this;
        }

        return $this->middlewarePipeline()->through($this, $middleware_list);
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
     * Track an executed middleware.
     *
     * @param string $middleware Middleware identifier
     * @return self
     */
    public function addCalledMiddleware(string $middleware): self
    {
        $this->called_middlewares[] = $middleware;
        return $this;
    }

    /**
     * Resolve the configured middleware pipeline or create an isolated fallback.
     *
     * @return MiddlewarePipeline
     */
    private function middlewarePipeline(): MiddlewarePipeline
    {
        try {
            return app(MiddlewarePipeline::class);
        } catch (RuntimeException) {
            return new MiddlewarePipeline();
        }
    }

    /**
     * Update request url history
     *
     * @return void
     */
    public function updateUrlHistory(): void
    {
        $session = $this->session();
        $history = $session->get('cubeHttpUrlHistory', []);
        $last_url = array_get_last($history) ?? '';

        if ($last_url === $this->url()->getUrl()) {
            return;
        }

        $history[] = $this->url()->getUrl();
        $session->put('cubeHttpUrlHistory', $history);
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
        return null; //$this->session->get('cubeHttpRequest');
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
            new Headers(),
            new Collection($_COOKIE),
            new Collection($_GET),
            new Collection($_POST),
            new Collection($_FILES),
            null,
            (string) file_get_contents('php://input'),
        );
    }
}
