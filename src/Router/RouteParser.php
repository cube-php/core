<?php

namespace Cube\Router;

use InvalidArgumentException;

use Cube\Router\Route;
use Cube\Router\RouteCollection;

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
        '*int' => '([0-9]?+)',
        '*string' => '([\w]?+)',
        '*bool' => '(true|false)?',
        '*any' => '(.*?)',
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
     * @param Route
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
        $regexp_path = '';
        $rawpath = $this->_route->getPath();

        $path = $this->compileRegularPath($rawpath);
        $path = $this->compileStrictPathParams($path);

        return $this->_addRemoveTrailingSlash($path, $this->_route->hasOptionalParameter());
    }
    
    /**
     * Compile regular path conditions
     * 
     * @param string $path Path to compile
     * 
     * @return string Compiled path
     */
    private function compileRegularPath($path)
    {

        #check for regular path conditions
        $regular_path = preg_match_all('#(\{(.*?)\})#', $path, $matches);
        
        if(!$regular_path) return $path;

        $newpath = $path;

        foreach($matches[1] as $index => $match) {

            #remove brackets
            $regexps = static::$regex;
            $match_without_brackets = str_replace(['{','}'], '', $match);
            $last_char = substr($match_without_brackets, -1, 1);

            $is_optional = $last_char === '?';
            $match_vars = explode(':', $match_without_brackets);
            $match_vars_count = count($match_vars);
            $isRegularPath = preg_match('/\:/', $match);

            if(!$isRegularPath) {
                continue;
            }

            if($match_vars_count < 2 || $match_vars_count > 2) {
                throw new InvalidArgumentException('Invalid route path for route "' . $path . '"');
            }

            #get parameter values
            $parameter_name = $match_vars[1];
            $parameter_raw_value = trim($match_vars[0]);
            $parameter_value = '';

            #Check if it has optional parameters
            $last_char = substr($parameter_name, -1, 1);
            $is_optional = $last_char === '?';

            #Specify attribute's index to route
            $this->_route->setAttribute($parameter_name);
            $this->_route->setHasOptionalParameter($is_optional);

            if(array_key_exists($parameter_raw_value, $regexps)){
                $regexp = $is_optional ? static::$regex_opt : $regexps;
                $parameter_value = $regexp[$parameter_raw_value] ?? null;
            }

            if(!$parameter_value) {
                $parameter_value = '(' . $parameter_raw_value . ')';
            }

            $newpath = str_replace($match, $parameter_value, $newpath);
        }

        return $newpath;
    }

    /**
     * Compile strict parameters path
     * 
     * @param string $path
     * 
     * @return string
     */
    private function compileStrictPathParams($path)
    {
        #check for strict parameters
        $strict_parameters = preg_match_all('#\{([^\/]+)\}#', $path, $matches);

        if(!$strict_parameters) return $path;

        $newpath = $path;

        foreach($matches[0] as $index => $match) {

            $parameter_name = str_replace(['{', '}'], '', $match);
            $last_char = substr($parameter_name, -1, 1);
            $is_optional = $last_char === '?';

            if($is_optional) {
                $parameter_name = substr($parameter_name, 0, strlen($parameter_name) - 1);
            }

            $this->_route->setAttribute($parameter_name);
            $this->_route->setHasOptionalParameter($is_optional);

            $replace_with = $is_optional ? static::$regex_opt : static::$regex;
            $newpath = str_replace($match, $replace_with['*any'], $newpath);
        }

        return $newpath;
    }

    /**
     * Add or remove trailing slash
     *
     * @param boolean $is_optional
     * @return string
     */
    private function _addRemoveTrailingSlash($path, $is_optional = false)
    {
        if(!$is_optional) {
            #Enforce trailing slash
            return (substr($path, -1, 1) === '/') ? $path : $path . '/';
        }

        #Remove trailing slash
        return (substr($path, -1, 1) === '/') ? substr($path, 0, strlen($path) - 1) : $path;
    }
}