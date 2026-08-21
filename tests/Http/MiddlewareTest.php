<?php

use Cube\Http\Middleware\MiddlewarePipeline;
use Cube\Http\Middleware\MiddlewareResolver;
use Cube\Http\Request;
use Cube\Http\Response;
use Cube\Interfaces\MiddlewareInterface;
use Cube\Misc\Collection;

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

it('rejects middleware aliases containing the argument delimiter', function () {
    new MiddlewareResolver([
        'auth:admin' => PipelineAttributeMiddleware::class,
    ]);
})->throws(InvalidArgumentException::class, 'Middleware keys must not contain :');
