<?php

namespace Cube\Router;

use Cube\App\App;
use Cube\App\Directory;
use Cube\Misc\File;
use Cube\Router\Attributes\Delete;
use Cube\Router\Attributes\Get;
use Cube\Router\Attributes\Patch;
use Cube\Router\Attributes\Post;
use Cube\Router\Attributes\Put;
use Cube\Router\Attributes\Route;
use Cube\Router\Attributes\RouteGroup;
use Cube\Router\Route as RouterRoute;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

class ControllerRoutesLoader
{
    /**
     * Load routes
     *
     * @return void
     */
    public static function load()
    {
        $routes = self::getRoutesToLoad();

        every(
            $routes,
            fn(RouterRoute $route) => RouteCollection::attachRoute($route)
        );
    }

    /**
     * Cache routes
     *
     * @return boolean
     */
    public static function cacheRoutes(): bool
    {
        $app_cache_dir = App::getRunningInstance()->getPath(
            Directory::PATH_CACHE
        );

        $dir = concat($app_cache_dir, DIRECTORY_SEPARATOR, 'router');

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $routes = self::loadRoutes();
        $data = array();

        every(
            $routes,
            function (RouterRoute $route) use (&$data) {
                $data[] = $route->toJSON();
            }
        );

        $filename = self::getCacheFileDir();
        file_put_contents($filename, json_encode($data));

        return true;
    }

    /**
     * Clear cached routes
     *
     * @return boolean
     */
    public static function clearCachedRoutes(): bool
    {
        $filedir = self::getCacheFileDir();

        if (!file_exists($filedir)) {
            return false;
        }

        try {
            unlink($filedir);
        } catch (Throwable $e) {
            return false;
        }

        return true;
    }

    /**
     * Load uncached routes
     *
     * @return RouterRoute[]
     */
    protected static function loadRoutes()
    {
        $controllers = self::scan();
        $routes = array();

        $route_verbs = array(
            Delete::class,
            Patch::class,
            Post::class,
            Get::class,
            Put::class
        );

        $route_attributes = array(
            Route::class,
            ...$route_verbs
        );

        every($controllers, function ($path) use (&$routes, $route_verbs, $route_attributes) {
            $fileinfo = (object) pathinfo($path->file);
            $filename = $fileinfo->filename;
            $filext = $fileinfo->extension;

            if ($filext !== 'php') {
                return;
            }

            $subdirs = $path->subdirs;
            $namespace = 'App\\Controllers\\';
            $namespace .= $subdirs ? implode('\\', $subdirs) . '\\' : '';

            $class = concat($namespace, $filename);
            $rf = new ReflectionClass($class);

            $rf_attr = $rf->getAttributes(RouteGroup::class);
            $attr = $rf_attr ? $rf_attr[0] : null;

            $group_middlewares = null;
            $group_path = null;
            $group_name = null;

            if ($attr) {
                $group_args = (object) $attr->getArguments();
                $group_middlewares = $group_args->use ?? null;
                $group_path = $group_args->path ?? null;
                $group_name = $group_args->name ?? null;
            }

            return every(
                $rf->getMethods(),
                function (ReflectionMethod $method) use (
                    &$routes,
                    $filename,
                    $route_attributes,
                    $group_middlewares,
                    $group_path,
                    $group_name,
                    $subdirs,
                    $route_verbs,
                ) {
                    $attributes = $method->getAttributes();

                    //Check if RouteGroup's path has trailing slash
                    $group_path = ($group_path && str_ends_with($group_path, '/'))
                        ? substr($group_path, 0, -1) : $group_path;

                    if (!$attributes) {
                        return;
                    }

                    $attribute = $attributes[0];
                    $name = $attribute->getName();

                    if (!in_array($name, $route_attributes)) {
                        return;
                    }

                    $args = (object) $attribute->getArguments();

                    if (in_array($name, $route_verbs)) {
                        $rmethod = array_get_last(explode('\\', $name));

                        $args = (object) array(
                            'method' => strtoupper($rmethod),
                            'path' => $args->path ?? $args->{0} ?? '/',
                            'name' => $args->name ?? $args?->{1} ?? null,
                            'use' => $args->use ?? $args?->{2} ?? null,
                        );
                    }

                    $path = $group_path ? $group_path . $args->path : $args->path;

                    $route = new RouterRoute(
                        controller: concat($filename, '.', $method->getName()),
                        parent_names: [$group_name],
                        method: $args?->method,
                        path: $path,
                    );

                    $middlewares = $args->use ?? [];
                    $name = $args->name ?? null;

                    if ($subdirs) {
                        $route->setNamespace($subdirs);
                    }

                    if ($group_middlewares) {
                        $middlewares = array_merge($group_middlewares, $middlewares);
                    }

                    if ($name) {
                        $route->name($name);
                    }

                    $route->use($middlewares);
                    $routes[] = $route;
                }
            );
        });

        return $routes;
    }

    /**
     * Get routes to load based on conditions
     *
     * @return RouterRoutes[]
     */
    protected static function getRoutesToLoad()
    {
        if (App::isDevelopment()) {
            return self::loadRoutes();
        }

        $cached_routes = self::loadCachedRoutes();

        if ($cached_routes) {
            return $cached_routes;
        }

        self::cacheRoutes();
        return self::loadCachedRoutes();
    }

    /**
     * Load cached routes
     *
     * @return RouterRoutes[]
     */
    protected static function loadCachedRoutes()
    {
        $routes_list = self::getCachedRoutes();

        if (!$routes_list) {
            return null;
        }

        $routes = [];

        every($routes_list, function ($data) use (&$routes) {
            $route = new RouterRoute(
                controller: $data->controller,
                method: $data->method,
                path: $data->path,
                parent_names: [],
            );

            $middlewares = $data->middlewares;
            $namespace = $data->namespace ?? null;
            $name = $data->name ?? null;

            if ($name) {
                $route->name($name);
            }

            if ($namespace) {
                $route->setNamespace($namespace);
            }

            if ($middlewares) {
                $route->use($middlewares);
            }

            $routes[] = $route;
        });

        return $routes;
    }

    /**
     * Get cache directory
     *
     * @return string
     */
    protected static function getCacheFileDir(): string
    {
        $cache_dir = App::getRunningInstance()->getPath(
            Directory::PATH_CACHE
        );

        return File::joinPath($cache_dir, 'router', 'routes.json');
    }

    /**
     * Get cached routes
     *
     * @return array|null
     */
    protected static function getCachedRoutes(): array|null
    {
        $dir = self::getCacheFileDir();

        if (!file_exists($dir)) {
            return null;
        }

        $content = file_get_contents($dir);

        if (!$content) {
            return null;
        }

        $data = json_decode($content);

        if (json_last_error()) {
            return null;
        }

        return $data;
    }

    /**
     * Scan controller directory
     *
     * @return array
     */
    protected static function scan()
    {
        $controllers_path = App::getRunningInstance()->getPath(
            Directory::PATH_CONTROLLERS
        );

        return scan_directory($controllers_path);
    }
}
