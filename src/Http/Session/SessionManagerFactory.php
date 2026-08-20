<?php

namespace Cube\Http\Session;

use Cube\App\App;
use Cube\Http\Session\Stores\ArraySessionStore;
use Cube\Http\Session\Stores\DatabaseSessionStore;
use Cube\Http\Session\Stores\FileSessionStore;
use Cube\Http\Session\Stores\RedisSessionStore;
use InvalidArgumentException;

class SessionManagerFactory
{
    public const STORE_ARRAY = 'array';

    public const STORE_DATABASE = 'database';

    public const STORE_DEFAULT = 'default';

    public const STORE_FILE = 'file';

    public const STORE_REDIS = 'redis';

    /**
     * Create the configured session manager.
     *
     * @return SessionManager
     */
    public static function make(): SessionManager
    {
        $store = match (static::getStore()) {
            static::STORE_ARRAY => new ArraySessionStore(),
            static::STORE_DATABASE => new DatabaseSessionStore(),
            static::STORE_DEFAULT, static::STORE_FILE => new FileSessionStore(),
            static::STORE_REDIS => new RedisSessionStore(),
            default => throw new InvalidArgumentException(
                'Unsupported session store "' . static::getStore() . '"'
            ),
        };

        return new SessionManager($store);
    }

    /**
     * Get the configured session store name.
     *
     * @return string
     */
    public static function getStore(): string
    {
        $store = App::getConfig('app.session.store');
        $store = $store ?: App::getConfig('app.session');

        return strtolower((string) ($store ?: static::STORE_FILE));
    }
}
