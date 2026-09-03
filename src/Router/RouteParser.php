<?php

namespace Cube\Router;

use InvalidArgumentException;
use Cube\Router\Route;

class RouteParser
{

    /**
     * Route regexp matcher
     * 
     * @var array
     */
    private static $regex = array(
        '*int' => '([0-9]+)',
        '*string' => '([\w]+)',
        '*bool' => '(true|false)',
        '*any' => '([^\/]+)',
        '*all' => '(.*?)'
    );

    /**
     * Route regexp matcher
     * 
     * @var array
     */
    private static $regex_opt = array(
        '*int' => '([0-9]*)',
        '*string' => '([\w]*)',
        '*bool' => '(true|false)?',
        '*any' => '([^\/]*)',
        '*all' => '(.*?)'
    );

    /**
     * Route
     * 
     * @var Route
     */
    private $_route;

    /**
     * Class constructor
     * 
     * @param Route $route
     */
    public function __construct(Route $route)
    {
        $this->_route = $route;
    }

    /**
     * Parse route path and generate regular expression
     * 
     * @return string
     */
    public function regexp()
    {
        if ($this->_route->isParsed()) {
            return $this->_addRemoveTrailingSlash(
                $this->_route->getParsedPath(),
                $this->_route->hasOptionalParameter()
            );
        }

        $rawpath = $this->_route->getPath();
        $path = $this->compilePath($rawpath);
        $this->_route->setParsedPath($path);

        return $this->_addRemoveTrailingSlash(
            $path,
            $this->_route->hasOptionalParameter()
        );
    }

    /**
     * Compile route path into a safe regular expression fragment.
     *
     * @param string $path Path to compile
     * @return string
     */
    private function compilePath(string $path): string
    {
        $has_parameters = preg_match_all(
            '#\{([^{}\/]+)\}#',
            $path,
            $matches,
            PREG_OFFSET_CAPTURE
        );

        if (!$has_parameters) {
            return preg_quote($path, '#');
        }

        $compiled = '';
        $offset = 0;

        foreach ($matches[0] as $index => $match) {
            [$raw_placeholder, $position] = $match;
            $compiled .= preg_quote(
                substr($path, $offset, $position - $offset),
                '#'
            );

            $compiled .= $this->compileParameter(
                $matches[1][$index][0],
                $path
            );

            $offset = $position + strlen($raw_placeholder);
        }

        $compiled .= preg_quote(substr($path, $offset), '#');
        return $compiled;
    }

    /**
     * Compile route path parameter.
     *
     * @param string $parameter
     * @param string $path
     * @return string
     */
    private function compileParameter(string $parameter, string $path): string
    {
        $match_vars = explode(':', $parameter, 2);
        $type = count($match_vars) === 2 ? trim($match_vars[0]) : null;
        $name = count($match_vars) === 2 ? $match_vars[1] : $match_vars[0];
        $is_optional = substr($name, -1, 1) === '?';

        if ($is_optional) {
            $name = substr($name, 0, strlen($name) - 1);
        }

        if ($name === '') {
            throw new InvalidArgumentException('Invalid route path for route "' . $path . '"');
        }

        $this->_route->setAttribute($name, $type);
        $this->_route->setHasOptionalParameter($is_optional);

        if (!$type) {
            return ($is_optional ? static::$regex_opt : static::$regex)['*any'];
        }

        $regexps = $is_optional ? static::$regex_opt : static::$regex;

        if (array_key_exists($type, $regexps)) {
            return $regexps[$type];
        }

        return '(' . $this->validateCustomRegex($type, $path) . ')';
    }

    /**
     * Validate custom route regular expression.
     *
     * @param string $regex
     * @param string $path
     * @return string
     */
    private function validateCustomRegex(string $regex, string $path): string
    {
        if ($regex === '' || str_contains($regex, '#')) {
            throw new InvalidArgumentException('Invalid route path for route "' . $path . '"');
        }

        set_error_handler(fn() => true);
        $is_valid = preg_match('#^(' . $regex . ')$#', '') !== false;
        restore_error_handler();

        if (!$is_valid) {
            throw new InvalidArgumentException('Invalid route path for route "' . $path . '"');
        }

        return $regex;
    }

    /**
     * Add or remove trailing slash
     *
     * @param boolean $is_optional
     * @return string
     */
    private function _addRemoveTrailingSlash(string $path, bool $is_optional = false)
    {
        if (!$is_optional) {
            #Enforce trailing slash
            return (substr($path, -1, 1) === '/') ? $path : $path . '/';
        }

        #Remove trailing slash
        return (substr($path, -1, 1) === '/') ? substr($path, 0, strlen($path) - 1) : $path;
    }

    public static function attributeCast(string $value, ?string $type = null)
    {
        return match ($type) {
            '*bool' => strtolower($value) === 'true',
            '*int' => (int) $value,
            default => (string) $value
        };
    }
}
