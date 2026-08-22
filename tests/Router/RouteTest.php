<?php

use Cube\App\App;
use Cube\Http\Middleware\MiddlewarePipeline;
use Cube\Http\Middleware\MiddlewareResolver;
use Cube\Http\Request;
use Cube\Misc\Collection;
use Cube\Router\Route;

if (!class_exists('App\\Controllers\\RouteDispatchTargetController')) {
    eval('
        namespace App\\Controllers;

        class RouteDispatchTargetController extends \\Cube\\Http\\Controller
        {
            public function index()
            {
                return "target controller";
            }
        }
    ');
}

if (!class_exists('App\\Controllers\\RouteDispatchFallbackController')) {
    eval('
        namespace App\\Controllers;

        class RouteDispatchFallbackController extends \\Cube\\Http\\Controller
        {
            public function index()
            {
                return "fallback controller";
            }
        }
    ');
}

function bindRouteDispatchDependencies(): void
{
    $app = (new ReflectionClass(App::class))->newInstanceWithoutConstructor();
    $caches = new ReflectionProperty(App::class, 'caches');
    $caches->setValue($app, [
        'config' => [
            'view' => ['embed_request' => false],
            'middleware' => [],
        ],
    ]);

    app()->singleton(App::class, fn() => $app);
    $instances = new ReflectionProperty(app(), 'singletonInstances');
    $values = $instances->getValue(app());
    $values[App::class] = $app;
    $instances->setValue(app(), $values);

    app()->bind(
        MiddlewarePipeline::class,
        fn() => new MiddlewarePipeline(new MiddlewareResolver())
    );
}

function makeRouteDispatchRequest(): Request
{
    app()->resetScoped();

    return new Request(
        new Collection([
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'example.test',
            'REQUEST_URI' => '/dispatch',
        ]),
        new Collection(),
        new Collection(),
        new Collection(),
        new Collection(),
        new Collection(),
    );
}

it('executes the controller configured on the route', function () {
    bindRouteDispatchDependencies();

    $route = new Route(
        'GET',
        '/dispatch',
        'RouteDispatchTargetController.index',
    );

    $response = $route->handle(makeRouteDispatchRequest());

    expect($response->getBody())->toBe('target controller')
        ->and($response->getBody())->not->toBe('fallback controller');
});
