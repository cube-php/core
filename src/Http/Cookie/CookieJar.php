<?php

namespace Cube\Http\Cookie;

final class CookieJar
{
    protected array $cookies = [];

    public function add(CookieItem $cookie): void
    {
        $this->cookies[] = $cookie;
    }

    public function all(): array
    {
        return $this->cookies;
    }
}
