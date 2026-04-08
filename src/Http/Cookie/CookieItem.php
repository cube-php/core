<?php

namespace Cube\Http\Cookie;

final readonly class CookieItem
{
    public function __construct(
        public string $name,
        public string $value,
        public int $expires = 0,
        public string $path = '/',
        public string $domain = '',
        public bool $secure = false,
        public bool $httponly = true,
        public string $samesite = 'Lax'
    ) {}
}
