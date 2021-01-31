<?php

namespace Cube\Http;

use InvalidArgumentException;
use Cube\App;
use Cube\Interfaces\UriInterface;

class Uri implements UriInterface
{
    /**
     * Url host name
     * 
     * @var string
     */
    private $_host = '';

    /**
     * Url scheme
     * 
     * @var string
     */
    private $_scheme = '';

    /**
     * Query string
     * 
     * @var string
     */
    private $_query = '';

    /**
     * Url port
     * 
     * @var int|null
     */
    private $_port = null;

    /**
     * Url pathname
     * 
     * @var string
     */
    private $_path = '/';

    /**
     * Query string in array format
     * 
     * @var string[]
     */
    private $_parsed_query;

    private $_usual_ports = [80, 443];
    
    /**
     * Uri constructor
     * 
     * @param string|null $url
     */
    public function __construct($url = null)
    {
        if($url) $this->parse($url);
    }

    /**
     * Uri behaviour when treated as string
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->getFullUrl();
    }

    /**
     * Return full url
     * 
     * @return string
     */
    public function getFullUrl()
    {
        $query = ($this->_query) ? '?' . $this->_query : '';
        return $this->getUrl() . $query;
    }

    /**
     * Get url pathname
     * 
     * @return string
     */
    public function getPath()
    {
        return $this->_path;
    }

    /**
     * Get url port
     * 
     * @return int|null
     */
    public function getPort()
    {
        return (int) $this->_port;
    }

    /**
     * Returns url without query string
     * 
     * @return string
     */
    public function getUrl($with_scheme = true)
    {
        $url = $with_scheme ? $this->_scheme . '://' : '';
        $url .= $this->_host;

        if($this->getPort() && !in_array($this->getPort(), $this->_usual_ports)) {
            $url .= ':' . $this->getPort();
        }

        $url .= $this->_path;
        return $url;
    }

    /**
     * Returns url without scheme
     * 
     * @return string
     */
    public function getUrlWithoutScheme()
    {
        return $this->getUrl(false);
    }

    /**
     * Return url host name
     * 
     * @return string
     */
    public function getHost()
    {
        return $this->_host;
    }

    /**
     * Get the host name
     *
     * @return string
     */
    public function getHostName()
    {
        $url = $this->getScheme() . '://' . $this->getHost();

        if($this->getPort()) {
            $url .= ':' . $this->getPort();
        }

        return $url;
    }

    /**
     * Return url scheme
     * 
     * @return string
     */
    public function getScheme()
    {
        return $this->_scheme;
    }

    /**
     * Return query
     * 
     * @param string $name Name fields
     * @param mixed $otherwise Another value if then query key doesn't exist
     * @return string
     */
    public function getQuery($name, $otherwise = null)
    {
        $name_vars = explode(',', $name);
        $name_vars_count = count($name_vars);

        if($name_vars_count == 1) {
            return $this->_parsed_query[$name] ?? $otherwise;
        }

        $name_vars_trimmed = array_map('trim', $name_vars);
        $keys = [];

        foreach($name_vars_trimmed as $index => $key) {
            $selected_otherwise_value = is_array($otherwise) ?
                $otherwise[$index] ?? null : $otherwise;

            $keys[] = $this->_parsed_query[$key] ?? $selected_otherwise_value;
        }

        return $keys;
    }

    /**
     * Return query params
     * 
     * @return string
     */
    public function getQueryParams() {
        return $this->_query;
    }

    /**
     * Check if string is a valid url
     * 
     * @param $str String to check
     * 
     * @return bool
     */
    public function isUri($str)
    {
        return filter_var($str, FILTER_VALIDATE_URL);
    }

    /**
     * Check if url matches specified $path
     *
     * @param string $path Path to check match
     * @return boolean
     */
    public function matches($path)
    {
        return !!preg_match("#^{$path}#", $this->getPath());
    }

    /**
     * Parse url string to provide all details
     * 
     * @param string $url Url to parse
     * 
     * @return void
     */
    public function parse($url)
    {
        if(!$this->isUri($url)) {
            throw new InvalidArgumentException('Argument "$url" is not a valid url');
        }

        $config = App::getConfigByName('app');
        $directory = $config['directory'];
        $url_data = (object) parse_url($url);

        #Let's get scheme
        $this->_scheme = $url_data->scheme;

        #Let's get host name
        $this->_host = $url_data->host;

        if($directory) {
            $this->_host .= $directory;
        }

        #Get query string
        $query_string = explode('?', $url)[1] ?? null;
        $this->_query = $query_string;

        #Get port number
        $this->_port = $url_data->port ?? null;

        #Get url path
        $this->_path = $url_data->path;
        if($directory) {
            $this->_path = preg_replace("#{$directory}#", "", $this->_path);
        }

        #Parse query params
        $this->parseQueryParams();
    }

    /**
     * Parse query string to array
     * 
     * @return string[]
     */
    private function parseQueryParams()
    {
        $query = $this->_query;
        $data = array();

        if(!$query) return $data;

        parse_str($query, $data);
        $context = array();

        foreach($data as $field => $value) {
            $context[$field] = htmlspecialchars($value);
        }

        $this->_parsed_query = $context;
        return $this;
    }
}