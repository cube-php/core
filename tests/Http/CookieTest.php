<?php

use Cube\Http\Cookie\CookieItem;

it('normalizes cookie same site values and forces secure for same site none', function () {
    $cookie = new CookieItem(
        name: 'remember',
        value: 'token',
        secure: false,
        httponly: true,
        samesite: 'none',
    );

    expect($cookie->samesite)->toBe(CookieItem::SAMESITE_NONE)
        ->and($cookie->secure)->toBeTrue()
        ->and($cookie->httponly)->toBeTrue();
});

it('defaults invalid cookie same site values to lax', function () {
    $cookie = new CookieItem('theme', 'dark', samesite: 'invalid');

    expect($cookie->samesite)->toBe(CookieItem::SAMESITE_LAX);
});
