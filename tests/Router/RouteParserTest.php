<?php

use Cube\Router\Route;

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
