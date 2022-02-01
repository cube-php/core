<?php

use Cube\Http\Request;
use Cube\App\App;
use Cube\Exceptions\RouteException;
use Cube\Router\RouteCollection;

/**
 * Get route's path from it's name
 *
 * @param string $name
 * @param array|null $params
 * @return string|null
 */
function route(string $name, ?array $params = null, ?array $query = null) {
    $route = RouteCollection::getRouteFromName($name);

    if(!$route) {
        return null;
    }

    $new_params = [];
    every($route->getParams(), function ($value, $key) use ($params, &$new_params, $route) {

        if(!isset($params[$value])) {
            throw new RouteException(
                concat('Parameter "', $value ,'" not specified for route ', $route->getName())
            );
        }

        $new_params[$key] = $params[$value] ?? $value;
    });

    $uri = strtr($route->getPath(), $new_params);
    return url($uri, $query);
}

/**
 * Return full url based on specified path and query parameters
 *
 * @param string|array $path Path to concantenate with URL
 * @param null|array $query Query string
 * @return string
 */
function url($path = '', ?array $query = null) : string
{
    $request = Request::getRunningInstance();

    if(is_array($path)) {
        $path = sprintf('/%s', implode('/', $path));
    }

    $repath = $request->url()->getHostName() . $path;

    return $query ?
        $repath . '?' . http_build_query($query) : $repath;
}

/**
 * Return assets based url
 *
 * @param string $asset_path Asset path
 * @param bool $should_cache
 * @return string
 */
function asset($asset_path, bool $should_cache = false) : string
{
    $full_path = array_merge(['assets'], array_wrap($asset_path));
    $query = $should_cache ? ['v' => asset_token()] : null;
    return url($full_path, $query);
}

/**
 * Load javascript files
 * 
 * @return string
 */
function jscript($name, $no_cache = null) : string
{
    if(is_array($name)) {

        $links = '';
        
        foreach($name as $name) {
            $links .= "\n"  . jscript($name, $no_cache);
        }

        return $links;
    }

    $asset = asset(['js', $name . '.js'], true);

    return h('script', ['src' => $asset]);
}


/**
 * Load javascript files
 * 
 * @return string
 */
function css($name, $no_cache = null) : string
{
    if(is_array($name)) {

        $links = '';
        
        foreach($name as $name) {
            $links .= "\n"  . css($name, $no_cache);
        }

        return $links;
    }

    $asset = asset(['css', $name . '.css'], true);

    return h('link', [
        'rel' => 'stylesheet',
        'href' => $asset
    ]);
}

function asset_token() : string {
    if(App::isDevelopment()) {
        return time();
    }

    return md5(env('ASSET_VERSION'));
}