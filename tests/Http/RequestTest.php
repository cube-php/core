<?php

use Cube\Http\Request;
use Cube\Misc\Collection;

function makeHttpRequest(
    string $method = 'POST',
    string $uri = '/submit',
    array $query = [],
    array $post = [],
    string $content = '',
    array $server = [],
): Request {
    app()->resetScoped();

    return new Request(
        new Collection(array_merge([
            'REQUEST_METHOD' => $method,
            'HTTP_HOST' => 'example.test',
            'REQUEST_URI' => $uri,
        ], $server)),
        new Collection(),
        new Collection(),
        new Collection($query),
        new Collection($post),
        new Collection(),
        null,
        $content,
    );
}

it('parses json request bodies and exposes nested inputs', function () {
    $request = makeHttpRequest(
        content: json_encode([
            'name' => 'Ada',
            'profile' => ['email' => 'ada@example.test'],
            'active' => true,
        ]),
    );

    expect($request->getBody())->toBe('{"name":"Ada","profile":{"email":"ada@example.test"},"active":true}')
        ->and($request->getParsedBody()->profile->email)->toBe('ada@example.test')
        ->and($request->input('name')->getValue())->toBe('Ada')
        ->and($request->input('profile.email')->getValue())->toBe('ada@example.test')
        ->and($request->input('active')->toBoolean())->toBeTrue();
});

it('uses post fields as the request body before raw content', function () {
    $request = makeHttpRequest(
        post: ['title' => 'From post'],
        content: '{"title":"From raw body"}',
    );

    expect($request->getBody())->toBe('{"title":"From post"}')
        ->and($request->input('title')->getValue())->toBe('From post');
});

it('returns defaults and multiple requested inputs', function () {
    $request = makeHttpRequest(content: 'name=Ada&role=admin');

    [$name, $missing] = $request->input('name, missing', 'guest,fallback');

    expect($name->getValue())->toBe('Ada')
        ->and($missing->getValue())->toBe('fallback')
        ->and($request->input('unknown', 'default')->getValue())->toBe('default');
});

it('ignores request bodies for get requests', function () {
    $request = makeHttpRequest(
        method: 'GET',
        query: ['page' => '2'],
        content: '{"ignored":true}',
    );

    expect($request->getMethod())->toBe('get')
        ->and($request->getBody())->toBeNull();
});

it('stores attributes and custom request methods', function () {
    $request = makeHttpRequest();

    $request
        ->setAttribute('userId', 42)
        ->setCustomMethod('tenant', fn () => 'acme');

    expect($request->userId)->toBe(42)
        ->and($request->getAttribute('missing', 'fallback'))->toBe('fallback')
        ->and($request->tenant())->toBe('acme');
});
