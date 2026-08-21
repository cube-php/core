<?php

use Cube\Router\Attributes\Get;
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
