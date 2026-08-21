<?php

use Cube\Router\RouteCollection;

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
