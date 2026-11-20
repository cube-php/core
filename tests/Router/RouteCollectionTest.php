<?php

use Cube\Router\RouteCollection;
use Cube\Router\Router;

beforeEach(fn() => resetRouteCollection());

it('matches static routes before dynamic routes for the same method and path bucket', function () {
    $static = attachRoute('GET', '/users', 'Users.index');
    attachRoute('GET', '/users/{id}', 'Users.show');

    $match = RouteCollection::matchRoute(makeRequest('GET', '/users'));

    expect($match)->not->toBeNull()
        ->and($match->route)->toBe($static)
        ->and($match->params)->toBe([]);
});

it('matches any routes for concrete HTTP methods', function () {
    $static = attachRoute(null, '/health', 'Health.show');
    $dynamic = attachRoute(null, '/hooks/{id}', 'Hooks.receive');

    $staticMatch = RouteCollection::matchRoute(makeRequest('POST', '/health'));
    $dynamicMatch = RouteCollection::matchRoute(makeRequest('PATCH', '/hooks/abc'));

    expect($staticMatch)->not->toBeNull()
        ->and($staticMatch->route)->toBe($static)
        ->and($dynamicMatch)->not->toBeNull()
        ->and($dynamicMatch->route)->toBe($dynamic)
        ->and($dynamicMatch->params[1])->toBe('abc');
});

it('preserves dynamic route registration order across buckets', function () {
    $wildcard = attachRoute('GET', '/{section}/settings', 'Settings.show');
    attachRoute('GET', '/users/{id}', 'Users.show');

    $match = RouteCollection::matchRoute(makeRequest('GET', '/users/settings'));

    expect($match)->not->toBeNull()
        ->and($match->route)->toBe($wildcard)
        ->and($match->params[1])->toBe('users');
});

it('finds routes named after router registration', function (?string $method, string $path) {
    $route = (new Router())->on($method, $path, 'Users.show')->name('users.show');

    expect(RouteCollection::getRouteFromName('users.show'))->toBe($route);
})->with([
    'static' => ['GET', '/users'],
    'dynamic' => ['GET', '/users/{id}'],
    'any' => [null, '/users'],
]);

it('includes nested group names in named route lookup', function () {
    $router = new Router();
    $router->group('/api')->name('api')->register(function (Router $router) {
        $router->group('/users')->name('users')->register(function (Router $router) {
            $router->get('/{id}', 'Users.show')->name('show');
        });
    });

    $route = RouteCollection::getRouteFromName('api.users.show');

    expect($route)->not->toBeNull()
        ->and($route->getPath())->toBe('/api/users/{id}')
        ->and(RouteCollection::getRouteFromName('show'))->toBeNull();
});

it('registers preassigned names only when the route is attached', function () {
    $route = makeRoute('GET', '/users')->name('users.old')->name('users.index');

    expect(RouteCollection::getRouteFromName('users.index'))->toBeNull();

    RouteCollection::attachRoute($route);

    expect(RouteCollection::getRouteFromName('users.index'))->toBe($route)
        ->and(RouteCollection::getRouteFromName('users.old'))->toBeNull();
});

it('updates named route lookup when an attached route is renamed', function () {
    $route = makeRoute('GET', '/users')->name('users.old');
    RouteCollection::attachRoute($route);

    $route->name('users.index')->name('users.index');

    expect(RouteCollection::getRouteFromName('users.index'))->toBe($route)
        ->and(RouteCollection::getRouteFromName('users.old'))->toBeNull();

    $route->name('');

    expect(RouteCollection::getRouteFromName('users.index'))->toBeNull();
});

it('rejects duplicate names without changing either registered route', function () {
    $router = new Router();
    $first = $router->get('/users', 'Users.index')->name('users.index');
    $second = $router->get('/users/{id}', 'Users.show')->name('users.show');

    expect(fn() => $second->name('users.index'))
        ->toThrow(InvalidArgumentException::class, "Route name 'users.index' is already in use.");

    expect(RouteCollection::getRouteFromName('users.index'))->toBe($first)
        ->and(RouteCollection::getRouteFromName('users.show'))->toBe($second)
        ->and($second->getName())->toBe('users.show');
});

it('does not restore cleared routes when their names change', function () {
    $route = (new Router())->get('/users', 'Users.index')->name('users.index');
    RouteCollection::reset();

    $route->name('users.renamed');

    expect(RouteCollection::getRouteFromName('users.index'))->toBeNull()
        ->and(RouteCollection::getRouteFromName('users.renamed'))->toBeNull()
        ->and(RouteCollection::all())->toBe([]);
});
