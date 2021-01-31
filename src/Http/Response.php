<?php

namespace Cube\Http;

use InvalidArgumentException;
use Cube\Interfaces\ResponseInterface;
use Cube\Helpers\View;

use Cube\Http\Headers;
use Cube\Http\Session;

class Response implements ResponseInterface
{
    public const HTTP_CONTINUE = 100;
    public const HTTP_SWITCHING_PROTOCOLS = 101;
    public const HTTP_PROCESSING = 102;

    public const HTTP_OK = 200;
    public const HTTP_CREATED = 201;
    public const HTTP_ACCEPTED = 202;
    public const HTTP_NON_AUTHORATIVE_INFORMATION = 203;
    public const HTTP_NO_CONTENT = 204;
    public const HTTP_RESET_CONTENT = 205;
    public const HTTP_PARTIAL_CONTENT = 206;
    public const HTTP_MULTI_STATUS = 207;
    public const HTTP_ALREADY_REPORTED = 208;
    public const HTTP_IM_USED = 226;

    public const HTTP_MULTIPLE_CHOICES = 300;
    public const HTTP_MOVED_PERMANENTLY = 301;
    public const HTTP_FOUND = 302;
    public const HTTP_SEE_OTHER = 303;
    public const HTTP_NOT_MODIFIED = 304;
    public const HTTP_USE_PROXY = 305;
    public const HTTP_SWITCH_PROXY = 306;
    public const HTTP_TEMPORARY_REDIRECT = 307;
    public const HTTP_PERMANENT_REDIRECT = 308;

    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNATHORIZED = 401;
    public const HTTP_PAYMENT_REQUIRED = 402;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_METHOD_NOT_ALLOWED = 405;
    public const HTTP_NOT_ACCEPTABLE = 406;
    public const HTTP_PROXY_AUTHENTICATION_REQUIRED = 407;
    public const HTTP_REQUEST_TIMEOUT = 408;
    public const HTTP_CONFLICT = 409;
    public const HTTP_GONE = 410;
    public const HTTP_LENGTH_REQUIRED = 411;
    public const HTTP_PRECONDITION_FAILED = 412;
    public const HTTP_PAYLOAD_TOO_LARGE = 413;
    public const HTTP_URI_TOO_LONG = 414;
    public const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;
    public const HTTP_RANGE_NOT_SATISFIABLE = 416;
    public const HTTP_EXPECTATION_FAILED = 417;
    public const HTTP_IM_A_TEAPOT = 418;
    public const HTTP_MISDIRECTED_REQUEST = 421;
    public const HTTP_UNPORCESSABLE_ENTITY = 422;
    public const HTTP_LOCKED = 423;
    public const HTTP_FAILED_DEPENDENCY = 424;
    public const HTTP_UPGRADE_REQUIRED = 426;
    public const HTTP_PRECONDITION_REQUIRED = 428;
    public const HTTP_TOO_MANY_REQUESTS = 429;
    public const HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
    public const HTTP_UNAVAILABLE_FOR_LEGAL_REASONS = 451;
    
    public const HTTP_INTERNAL_SERVER_ERROR = 500;
    public const HTTP_NOT_IMPLEMENTED = 501;
    public const HTTP_BAD_GATEWAY = 502;
    public const HTTP_SERVICE_UNAVAILABLE = 503;
    public const HTTP_GATEWAY_TIMEOUT = 504;
    public const HTTP_VERSION_NOT_SUPPORTED = 505;
    public const HTTP_VARIANT_ALSO_NEGOTIATES = 506;
    public const HTTP_INSUFFICIENT_STORAGE = 507;
    public const HTTP_LOOP_DETECTED = 508;
    public const HTTP_NOT_EXTENDED = 510;
    public const HTTP_NETWORK_AUTHENTICATION_REQUIRED = 511;


    /**
     * View context
     *
     * @var array
     */
    private $_view_context = [];

    /**
     * Response codes
     * 
     * @var string[]
     */
    private static $response_codes = array(

        //1xx Informational responses
        self::HTTP_CONTINUE => 'Continue',
        self::HTTP_SWITCHING_PROTOCOLS => 'Switching Protocols',
        self::HTTP_PROCESSING => 'Processing',

        //2xx Success
        self::HTTP_OK => 'OK',
        self::HTTP_CREATED => 'Created',
        self::HTTP_ACCEPTED => 'Accepted',
        self::HTTP_NON_AUTHORATIVE_INFORMATION => 'Non-Authorative Information',
        self::HTTP_NO_CONTENT => 'No Content',
        self::HTTP_RESET_CONTENT => 'Reset Content',
        self::HTTP_PARTIAL_CONTENT => 'Partial Content',
        self::HTTP_MULTI_STATUS => 'Multi Status',
        self::HTTP_ALREADY_REPORTED => 'Already Reported',
        self::HTTP_IM_USED => 'IM Used',

        //3xx Redirection
        self::HTTP_MULTIPLE_CHOICES => 'Multiple Choices',
        self::HTTP_MOVED_PERMANENTLY => 'Moved permanently',
        self::HTTP_FOUND => 'Found',
        self::HTTP_SEE_OTHER => 'See Other',
        self::HTTP_NOT_MODIFIED => 'Not Modified',
        self::HTTP_USE_PROXY => 'Use Proxy',
        self::HTTP_SWITCH_PROXY => 'Switch Proxy',
        self::HTTP_TEMPORARY_REDIRECT => 'Temporary Redirect',
        self::HTTP_PERMANENT_REDIRECT => 'Permanent Redirect',

        //4xx Client errors
        self::HTTP_BAD_REQUEST => 'Bad Request',
        self::HTTP_UNATHORIZED => 'Unauthorized',
        self::HTTP_PAYMENT_REQUIRED => 'Payment Required',
        self::HTTP_FORBIDDEN => 'Forbidden',
        self::HTTP_NOT_FOUND => 'Not Found',
        self::HTTP_METHOD_NOT_ALLOWED => 'Method Not Allowed',
        self::HTTP_NOT_ACCEPTABLE => 'Not Acceptable',
        self::HTTP_PROXY_AUTHENTICATION_REQUIRED => 'Proxy Authentication Required',
        self::HTTP_REQUEST_TIMEOUT => 'Request Timeout',
        self::HTTP_CONFLICT => 'Conflict',
        self::HTTP_GONE => 'Gone',
        self::HTTP_LENGTH_REQUIRED => 'Length Required',
        self::HTTP_PRECONDITION_FAILED => 'Precondition Failed',
        self::HTTP_PAYLOAD_TOO_LARGE => 'Payload Too Large',
        self::HTTP_URI_TOO_LONG => 'URI Too Long',
        self::HTTP_UNSUPPORTED_MEDIA_TYPE => 'Unsupported Media Type',
        self::HTTP_RANGE_NOT_SATISFIABLE => 'Range Not Satisfiable',
        self::HTTP_EXPECTATION_FAILED => 'Expectation Failed',
        self::HTTP_IM_A_TEAPOT => 'I\'m a teapot',
        self::HTTP_MISDIRECTED_REQUEST => 'Misdirected Request',
        self::HTTP_UNPORCESSABLE_ENTITY => 'Unprocessable Entity',
        self::HTTP_LOCKED => 'Locked',
        self::HTTP_FAILED_DEPENDENCY => 'Failed Dependency',
        self::HTTP_UPGRADE_REQUIRED => 'Upgrade Required',
        self::HTTP_PRECONDITION_REQUIRED => 'Precondition Required',
        self::HTTP_TOO_MANY_REQUESTS => 'Too Many Requests',
        self::HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE => 'Request Header Fields Too Large',
        self::HTTP_UNAVAILABLE_FOR_LEGAL_REASONS => 'Unavailable For Legal Reasons',

        //5xx Server Error
        self::HTTP_INTERNAL_SERVER_ERROR => 'Internal Server Error',
        self::HTTP_NOT_IMPLEMENTED => 'Not Implemented',
        self::HTTP_BAD_GATEWAY => 'Bad Gateway',
        self::HTTP_SERVICE_UNAVAILABLE => 'Service Unavailable',
        self::HTTP_GATEWAY_TIMEOUT => 'Gateway Timeout',
        self::HTTP_VERSION_NOT_SUPPORTED => 'HTTP Version Not Supported',
        self::HTTP_VARIANT_ALSO_NEGOTIATES => 'Variant Also Negotiates',
        self::HTTP_INSUFFICIENT_STORAGE => 'Insufficient Storage',
        self::HTTP_LOOP_DETECTED => 'Loop Detected',
        self::HTTP_NOT_EXTENDED => 'Not Extended',
        self::HTTP_NETWORK_AUTHENTICATION_REQUIRED => 'Network Authentication Required'
     );

    /**
     * Headers
     * 
     * @var \Cube\Http\Headers;
     */
    private $_header;

    /**
     * Response body content
     * 
     * @var string
     */
    private $_body = '';

    /**
     * Set whether headers have been output
     * 
     * @var bool
     */
    private $_has_render_headers = false;
    
    /**
     * View
     *
     * @var View
     */
    private $_view;

    /**
     * Response instance
     *
     * @var self
     */
    private static $_instance;

    /**
     * Response Constructor
     * 
     */
    private function __construct()
    {
        $this->_header = new Headers;
        $this->_view = new View(VIEW_PATH);
    }

    /**
     * Variable Setter
     * 
     * @param string $name Variable name
     * @param string $value Variable value
     * 
     * @return
     */
    public function __set($name, $value)
    {
        $this->_view_context[$name] = $value;
    }

    /**
     * Return body when response is treated as sring
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->_body;
    }

    /**
     * Disable Cross Origin Resource Sharing
     *
     * @return self
     */
    public function disableCors()
    {
        return $this->setOrigin(url());
    }

    /**
     * Enable Cross Origin Resource Sharing
     *
     * @return self
     */
    public function enableCors()
    {
        return $this->setOrigin('*');
    }

    /**
     * Add new header to previously added header
     * 
     * @param string|int $name Header field name
     * @param string|int $value Header field value
     * 
     * @return self
     */
    public function withAddedHeader($name, $value)
    {
        $old_value = $this->_header->get($name) ?? '';
        $new_value = $old_value . ', ' . $value;

        $this->_header->set($name, $new_value);
        return $this;
    }

    /**
     * Add new header to headers
     * 
     * @param string|int $name Header field name
     * @param string|int $value Header field value
     * 
     * @return self
     */
    public function withHeader($name, $value)
    {
        $this->_header->set($name, $value);
        return $this;
    }

    /**
     * Add new session to response
     * 
     * @param string $name Session name
     * @param mixed $value Session value
     * 
     * @return self
     */
    public function withSession($name, $value)
    {
        Session::set($name, $value);
        return $this;
    }

    /**
     * Remove a header field from headers
     * 
     * @param string|int $name Header field name to remove
     * 
     * @return self
     */
    public function withoutHeader($name)
    {
        $this->_header->remove($name);
        return $this;
    }

    /**
     * Sets response status codes
     * 
     * @param int $code Status code
     * @param string $reason Response code reason
     * 
     * @return self
     */
    public function withStatusCode($code, $reason = '')
    {
        $code = (int) $code;

        if(!$code or $code < 100 or $code > 599) {
            throw new InvalidArgumentException('The HTTP status code specified is invalid');
        }

        $reason = ($reason) ? $reason : (static::$response_codes[$code] ?? '');

        $this->_header->raw("HTTP/1.1 {$code} {$reason}");
        return $this;
    }

    /**
     * Write content to response
     * 
     * @param array $data Data to write to response
     * 
     * @return self
     */
    public function write(...$args)
    {

        $string = implode($args);

        #Check if headers have been output
        #Else output headers
        if(!$this->_has_render_headers) {
            $this->_header->render();
            $this->_has_render_headers = true;
        }

        $this->_body .= $string;

        echo $string;
        return $this;
    }

    /**
     * Write json encoded string to response body
     * 
     * @param string
     * @param int|null $status_code
     * 
     * @return self
     */
    public function json($data, ?int $status_code = null) {

        $this->withHeader('Content-Type', 'application/json');
        $data = json_encode($data);

        if($status_code) {
            $this->withStatusCode($status_code);
        }

        return $this->write($data);
    }

    /**
     * Redirect response
     *
     * @param string $path Path to redirect to
     * @param array $query_params Query to attach to path
     * @param bool $external_location Set whether the path redirecting to is an external path
     * @return self
     */
    public function redirect($path, ?array $query_params = null, $external_location = false)
    {
        $redirect_location = $external_location ? $path : url($path, $query_params);
        return $this
            ->withStatusCode(301)
            ->withHeader('location', $redirect_location)
            ->write(null);
    }

    /**
     * Response redirect using route name
     *
     * @param string $route_name
     * @param array|null $params
     * @param array|null $query
     * @return Response
     */
    public function route(string $route_name, ?array $params = null, ?array $query = null)
    {
        $path = route($route_name, $params);

        if(!$path) {
            throw new InvalidArgumentException('Route with name "' . $route_name . '" is not assigned');
        }

        return $this->redirect($path, $query);
    }

    /**
     * Set response CORS Origin
     *
     * @param string $origin
     * @return self
     */
    public function setOrigin($origin)
    {
        $this->withHeader('Access-Control-Allow-Origin', $origin);
        return $this;
    }

    /**
     * Render view
     * 
     * @param string $path Path of view to render
     * @param array $context Context to render in view
     * 
     * @return self
     */
    public function view($path, array $context = [], $run_render = true)
    {
        $resolved_context = array_merge($this->_view_context, $context);
        $rendered_content = $this->_view->render($path, $resolved_context);

        if(!$run_render) {
            return $rendered_content;
        }

        echo $rendered_content;
        return $this;
    }

    /**
     * Get response interface
     *
     * @param boolean $force_new Set if a fresh instance of response is needed
     * @return self
     */
    public static function getInstance($force_new = false)
    {
        if($force_new) {
            return new self();
        }

        if(static::$_instance) {
            return static::$_instance;
        }

        static::$_instance = new self();
        return static::$_instance;
    }
}