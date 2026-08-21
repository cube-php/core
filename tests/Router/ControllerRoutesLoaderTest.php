<?php

use Cube\Router\Attributes\Any;
use Cube\Router\Attributes\Delete;
use Cube\Router\Attributes\Get;
use Cube\Router\Attributes\Patch;
use Cube\Router\Attributes\Post;
use Cube\Router\Attributes\Put;
use Cube\Router\Attributes\Route;
use Cube\Router\ControllerRoutesLoader;

it('detects route attributes when the controller method has no route group', function () {
    #[Attribute]
    class MarkerAttribute {}

    class AttributeOnlyController
    {
        #[MarkerAttribute]
        #[Get('/users', 'users.index', ['legacy'], ['auth'], ['csrf'])]
        public function index() {}
    }

    $finder = new ReflectionMethod(ControllerRoutesLoader::class, 'getRouteAttribute');
    $method = new ReflectionMethod(AttributeOnlyController::class, 'index');

    $attribute = $finder->invoke(null, $method, [
        Route::class,
        Get::class,
    ]);

    $arguments = $attribute->getArguments();

    expect($attribute->getName())->toBe(Get::class)
        ->and($arguments[0])->toBe('/users')
        ->and($arguments[1])->toBe('users.index')
        ->and($arguments[3])->toBe(['auth']);
});

it('normalizes all HTTP verb attributes into route methods', function (
    string $attribute,
    ?string $method,
) {
    $normalizer = new ReflectionMethod(ControllerRoutesLoader::class, 'getRouteVerbArguments');

    $arguments = $normalizer->invoke(null, $attribute, [
        '/items',
        'items.index',
        ['legacy'],
        ['auth'],
        ['csrf'],
    ]);

    expect($arguments->method)->toBe($method)
        ->and($arguments->path)->toBe('/items')
        ->and($arguments->name)->toBe('items.index')
        ->and($arguments->use)->toBe(['legacy'])
        ->and($arguments->middleware)->toBe(['auth'])
        ->and($arguments->withoutMiddleware)->toBe(['csrf']);
})->with([
    'GET' => [Get::class, 'GET'],
    'POST' => [Post::class, 'POST'],
    'PUT' => [Put::class, 'PUT'],
    'PATCH' => [Patch::class, 'PATCH'],
    'DELETE' => [Delete::class, 'DELETE'],
    'ANY' => [Any::class, null],
]);
