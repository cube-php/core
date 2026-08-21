<?php

use Cube\Http\Cookie\CookieItem;
use Cube\Http\Response;

function makeHttpResponse(): Response
{
    app()->resetScoped();

    return new Response();
}

it('writes response body chunks in order', function () {
    $response = makeHttpResponse();

    $response->write('Hello', ' ', 'Cube');

    expect($response->getBody())->toBe('Hello Cube');
});

it('serializes json responses and updates status and content type', function () {
    $response = makeHttpResponse();

    $response->json(['ok' => true], Response::HTTP_CREATED);

    expect($response->getBody())->toBe('{"ok":true}')
        ->and($response->getHttpStatusCode())->toBe(Response::HTTP_CREATED)
        ->and($response->getHttpReason())->toBe('Created')
        ->and($response->getHeaders()->get('Content-Type'))->toBe('application/json');
});

it('mutates headers by setting adding and removing values', function () {
    $response = makeHttpResponse();

    $response
        ->withHeader('Cache-Control', 'no-cache')
        ->withAddedHeader('Cache-Control', 'private')
        ->withoutHeader('Cache-Control');

    expect($response->getHeaders()->has('Cache-Control'))->toBeFalse();
});

it('sets cors origin headers', function () {
    $response = makeHttpResponse();

    $response->enableCors();

    expect($response->getHeaders()->get('Access-Control-Allow-Origin'))->toBe('*');
});

it('rejects invalid http status codes', function () {
    makeHttpResponse()->withStatusCode(99);
})->throws(InvalidArgumentException::class, 'The HTTP status code specified is invalid');

it('stores cookies with normalized same site and secure none semantics', function () {
    $response = makeHttpResponse();

    $response->withCookie(
        name: 'session',
        value: 'abc',
        expires: 3600,
        path: '/app',
        domain: 'example.test',
        secure: false,
        httponly: true,
        samesite: 'none',
    );

    $cookie = $response->getCookies()[0];

    expect($cookie)->toBeInstanceOf(CookieItem::class)
        ->and($cookie->name)->toBe('session')
        ->and($cookie->value)->toBe('abc')
        ->and($cookie->expires)->toBe(3600)
        ->and($cookie->path)->toBe('/app')
        ->and($cookie->domain)->toBe('example.test')
        ->and($cookie->secure)->toBeTrue()
        ->and($cookie->httponly)->toBeTrue()
        ->and($cookie->samesite)->toBe(CookieItem::SAMESITE_NONE);
});
