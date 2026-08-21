<?php

use Cube\Exceptions\AppException;
use Cube\Http\Controller;
use Cube\Http\Request;
use Cube\Http\Response;
use Cube\Misc\Collection;
use Cube\Misc\Components;
use Cube\Router\Route;

class ControllerProbe extends Controller
{
    public function component(string $name, array $args = [])
    {
        return $this->getComponent($name, $args);
    }
}

function makeControllerRequest(): Request
{
    app()->resetScoped();

    return new Request(
        new Collection([
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST' => 'example.test',
            'REQUEST_URI' => '/controller',
        ]),
        new Collection(),
        new Collection(),
        new Collection(),
        new Collection(),
        new Collection(),
    );
}

function analyzeControllerResult(callable $controller, ?Response $response = null): Response
{
    $route = new Route('GET', '/controller', $controller);
    $analyzer = new ReflectionMethod(Route::class, '_analyzeControllerResult');

    return $analyzer->invoke(
        $route,
        $controller,
        makeControllerRequest(),
        $response ?: new Response(),
    );
}

it('stores single and array controller middleware assignments in order', function () {
    $controller = new ControllerProbe();

    $controller->middleware('auth');
    $controller->middleware(['csrf', 'rate-limit']);

    expect($controller->getMiddlewares())->toBe([
        'auth',
        'csrf',
        'rate-limit',
    ]);
});

it('resolves registered components through controllers', function () {
    $component = 'controller_probe_' . uniqid();
    Components::register($component, fn(string $name) => 'hello ' . $name);

    $controller = new ControllerProbe();

    expect($controller->component($component, ['Ada']))->toBe('hello Ada');
});

it('writes string controller results into the response body', function () {
    $response = analyzeControllerResult(fn() => 'controller body');

    expect($response->getBody())->toBe('controller body')
        ->and($response->getHttpStatusCode())->toBe(Response::HTTP_OK);
});

it('serializes array controller results as json responses', function () {
    $response = analyzeControllerResult(fn() => ['ok' => true]);

    expect($response->getBody())->toBe('{"ok":true}')
        ->and($response->getHeaders()->get('Content-Type'))->toBe('application/json');
});

it('returns controller response instances unchanged', function () {
    $expected = (new Response())
        ->withStatusCode(Response::HTTP_CREATED)
        ->write('created');

    $response = analyzeControllerResult(fn() => $expected);

    expect($response)->toBe($expected)
        ->and($response->getHttpStatusCode())->toBe(Response::HTTP_CREATED)
        ->and($response->getBody())->toBe('created');
});

it('writes an empty body for null controller results', function () {
    $response = analyzeControllerResult(fn() => null);

    expect($response->getBody())->toBe('');
});

it('rejects unsupported controller result types', function () {
    analyzeControllerResult(fn() => new stdClass());
})->throws(AppException::class, 'Response not returned');
