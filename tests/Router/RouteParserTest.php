<?php

use Cube\Router\Route;
use Cube\Router\RouteParser;

function routeRegex(string $path): string
{
    $route = new Route('GET', $path, 'Files.show');

    return "#^{$route->path()->regexp()}$#";
}

it('escapes literal route path characters around parameters', function () {
    $route = new Route('GET', '/files/{name}.json', 'Files.show');
    $regex = "#^{$route->path()->regexp()}$#";

    expect(preg_match($regex, '/files/readme.json/'))->toBe(1)
        ->and(preg_match($regex, '/files/readmeXjson/'))->toBe(0);
});

it('rejects custom route regex using the route regex delimiter', function () {
    $route = new Route('GET', '/items/{foo#bar:id}', 'Items.show');

    expect(fn() => $route->path()->regexp())
        ->toThrow(InvalidArgumentException::class);
});

it('enforces trailing slashes for static and required parameter routes', function () {
    expect(preg_match(routeRegex('/about'), '/about/'))->toBe(1)
        ->and(preg_match(routeRegex('/about'), '/about'))->toBe(0)
        ->and(preg_match(routeRegex('/users/{id}'), '/users/ada/'))->toBe(1)
        ->and(preg_match(routeRegex('/users/{id}'), '/users/ada'))->toBe(0);
});

it('matches built-in required route regex types', function (
    string $path,
    string $matchingPath,
    string $rejectedPath,
) {
    $regex = routeRegex($path);

    expect(preg_match($regex, $matchingPath))->toBe(1)
        ->and(preg_match($regex, $rejectedPath))->toBe(0);
})->with([
    'int' => ['/users/{*int:id}', '/users/42/', '/users/ada/'],
    'string' => ['/users/{*string:slug}', '/users/ada_42/', '/users/ada-42/'],
    'bool true' => ['/flags/{*bool:enabled}', '/flags/true/', '/flags/yes/'],
    'bool false' => ['/flags/{*bool:enabled}', '/flags/false/', '/flags/0/'],
    'any' => ['/files/{*any:name}', '/files/readme.json/', '/files/docs/readme/'],
    'default any' => ['/files/{name}', '/files/readme.json/', '/files/docs/readme/'],
    'all' => ['/files/{*all:path}', '/files/docs/readme/', '/files/docs/readme'],
]);

it('matches built-in optional route regex types', function (
    string $path,
    string $emptyPath,
    string $matchingPath,
    string $rejectedPath,
) {
    $regex = routeRegex($path);

    expect(preg_match($regex, $emptyPath))->toBe(1)
        ->and(preg_match($regex, $matchingPath))->toBe(1)
        ->and(preg_match($regex, $rejectedPath))->toBe(0);
})->with([
    'int' => ['/users/{*int:id?}', '/users/', '/users/42', '/users/ada'],
    'string' => ['/users/{*string:slug?}', '/users/', '/users/ada_42', '/users/ada-42'],
    'bool true' => ['/flags/{*bool:enabled?}', '/flags/', '/flags/true', '/flags/yes'],
    'bool false' => ['/flags/{*bool:enabled?}', '/flags/', '/flags/false', '/flags/0'],
    'any' => ['/files/{*any:name?}', '/files/', '/files/readme.json', '/files/docs/readme'],
    'default any' => ['/files/{name?}', '/files/', '/files/readme.json', '/files/docs/readme'],
    'all' => ['/files/{*all:path?}', '/files/', '/files/docs/readme', '/docs/readme'],
]);

it('matches custom route regex parameters', function () {
    $regex = routeRegex('/orders/{[A-Z][A-Z][A-Z]-[0-9][0-9][0-9][0-9]:reference}');

    expect(preg_match($regex, '/orders/ABC-1234/'))->toBe(1)
        ->and(preg_match($regex, '/orders/abc-1234/'))->toBe(0);
});

it('matches custom route regex alternatives', function () {
    $regex = routeRegex('/status/{hello|world:id}');

    expect(preg_match($regex, '/status/hello/'))->toBe(1)
        ->and(preg_match($regex, '/status/world/'))->toBe(1)
        ->and(preg_match($regex, '/status/other/'))->toBe(0);
});

it('rejects invalid custom route regex parameters', function () {
    $route = new Route('GET', '/items/{a(:id}', 'Items.show');

    expect(fn() => $route->path()->regexp())
        ->toThrow(InvalidArgumentException::class);
});

it('tracks route attributes found while compiling regex', function () {
    $route = new Route('GET', '/users/{*int:id}/flags/{*bool:enabled}', 'Users.flags');

    $route->path()->regexp();
    [$id, $enabled] = $route->getAttributes();

    expect($id->name)->toBe('id')
        ->and($id->type)->toBe('*int')
        ->and($enabled->name)->toBe('enabled')
        ->and($enabled->type)->toBe('*bool');
});

it('casts matched route attributes by regex type', function () {
    expect(RouteParser::attributeCast('42', '*int'))->toBe(42)
        ->and(RouteParser::attributeCast('true', '*bool'))->toBeTrue()
        ->and(RouteParser::attributeCast('false', '*bool'))->toBeFalse()
        ->and(RouteParser::attributeCast('value', '*string'))->toBe('value');
});
