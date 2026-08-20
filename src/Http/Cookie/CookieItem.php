<?php

namespace Cube\Http\Cookie;

final readonly class CookieItem
{
    public const SAMESITE_LAX = 'Lax';

    public const SAMESITE_STRICT = 'Strict';

    public const SAMESITE_NONE = 'None';

    public string $name;

    public string $value;

    public int $expires;

    public string $path;

    public string $domain;

    public bool $secure;

    public bool $httponly;

    public string $samesite;

    public function __construct(
        string $name,
        string $value,
        int $expires = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httponly = true,
        string $samesite = self::SAMESITE_LAX
    ) {
        $this->name = $name;
        $this->value = $value;
        $this->expires = $expires;
        $this->path = $path;
        $this->domain = $domain;
        $this->samesite = static::normalizeSameSite($samesite);
        $this->secure = $this->samesite === self::SAMESITE_NONE ? true : $secure;
        $this->httponly = $httponly;
    }

    protected static function normalizeSameSite(string $samesite): string
    {
        return match (strtolower($samesite)) {
            'strict' => self::SAMESITE_STRICT,
            'none' => self::SAMESITE_NONE,
            default => self::SAMESITE_LAX,
        };
    }
}
