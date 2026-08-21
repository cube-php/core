<?php

use Cube\App\App;
use Cube\Http\Middleware\MiddlewarePipeline;
use Cube\Http\Middleware\MiddlewareResolver;
use Cube\Http\Request;
use Cube\Http\Response;
use Cube\Interfaces\MiddlewareInterface;
use Cube\Misc\Collection;
use Cube\Router\Route;

class PipelineAttributeMiddleware implements MiddlewareInterface
{
    public function trigger(Request $request, ?array $args = null)
    {
        $request->setAttribute('pipeline_args', $args);
        return $request;
    }
}

class PipelineShortCircuitMiddleware implements MiddlewareInterface
{
    public function trigger(Request $request, ?array $args = null)
    {
        return (new Response())->withStatusCode(Response::HTTP_FORBIDDEN)->write('blocked');
    }
}

if (!class_exists('App\\Controllers\\PipelineControllerMiddlewareController')) {
    eval('
        namespace App\\Controllers;

        class PipelineControllerMiddlewareController extends \\Cube\\Http\\Controller
        {
            public function __middleware(\\Cube\\Http\\Request $request, \\Cube\\Http\\Response $response)
            {
                return $request->setAttribute("controller_middleware", "from __middleware");
            }

            public function index(\\Cube\\Http\\Request $request, \\Cube\\Http\\Response $response)
            {
                return $request->getAttribute("controller_middleware");
            }
        }
    ');
}

function makeMiddlewareRequest(): Request
{
    app()->resetScoped();

    return new Request(
        new Collection([
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'example.test',
            'REQUEST_URI' => '/',
        ]),
        new Collection(),
        new Collection(),
        new Collection(),
        new Collection(),
        new Collection(),
    );
}

function bindMiddlewareRouteDependencies(): void
{
    $app = (new ReflectionClass(App::class))->newInstanceWithoutConstructor();
    $caches = new ReflectionProperty(App::class, 'caches');
    $caches->setValue($app, [
        'config' => [
            'view' => ['embed_request' => false],
        ],
    ]);

    app()->singleton(App::class, fn() => $app);
    app()->bind(
        MiddlewarePipeline::class,
        fn() => new MiddlewarePipeline(new MiddlewareResolver())
    );
}

it('runs class middleware through the pipeline and tracks stable identifiers', function () {
    $request = makeMiddlewareRequest();
    $pipeline = new MiddlewarePipeline(new MiddlewareResolver());

    $result = $pipeline->through($request, PipelineAttributeMiddleware::class . ':admin,write');

    expect($result)->toBe($request)
        ->and($request->getAttribute('pipeline_args'))->toBe(['admin', 'write'])
        ->and($request->getMiddlewares())->toBe([PipelineAttributeMiddleware::class]);
});

it('passes string arguments to aliased object middleware', function () {
    $request = makeMiddlewareRequest();
    $middleware = new PipelineAttributeMiddleware();
    $pipeline = new MiddlewarePipeline(new MiddlewareResolver([
        'auth' => $middleware,
    ]));

    $pipeline->through($request, 'auth:admin');

    expect($request->getAttribute('pipeline_args'))->toBe(['admin'])
        ->and($request->getMiddlewares())->toBe([PipelineAttributeMiddleware::class]);
});

it('passes string arguments to aliased callable middleware', function () {
    $request = makeMiddlewareRequest();
    $pipeline = new MiddlewarePipeline(new MiddlewareResolver([
        'mark' => function (Request $request, ?array $args = null) {
            return $request->setAttribute('callable_args', $args);
        },
    ]));

    $pipeline->through($request, 'mark:one,two');

    expect($request->getAttribute('callable_args'))->toBe(['one', 'two'])
        ->and($request->getMiddlewares())->toBe([Closure::class]);
});

it('stops the pipeline when middleware returns a response', function () {
    $request = makeMiddlewareRequest();
    $pipeline = new MiddlewarePipeline(new MiddlewareResolver());

    $result = $pipeline->through($request, [
        PipelineShortCircuitMiddleware::class,
        fn(Request $request) => $request->setAttribute('should_not_run', true),
    ]);

    expect($result)->toBeInstanceOf(Response::class)
        ->and($result->getHttpStatusCode())->toBe(Response::HTTP_FORBIDDEN)
        ->and($result->getBody())->toBe('blocked')
        ->and($request->getAttribute('should_not_run'))->toBeNull()
        ->and($request->getMiddlewares())->toBe([PipelineShortCircuitMiddleware::class]);
});

it('runs controller __middleware hooks created on the fly', function () {
    bindMiddlewareRouteDependencies();

    $route = new Route(
        'GET',
        '/controller-middleware',
        'PipelineControllerMiddlewareController.index',
    );

    $response = $route->handle(makeMiddlewareRequest());

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->getBody())->toBe('from __middleware');
});

it('rejects middleware aliases containing the argument delimiter', function () {
    new MiddlewareResolver([
        'auth:admin' => PipelineAttributeMiddleware::class,
    ]);
})->throws(InvalidArgumentException::class, 'Middleware keys must not contain :');
