<?php

namespace Cube\Http\Session\Stores;

use Cube\App\App;
use Cube\Http\Session\SessionStoreInterface;
use Cube\Modules\Redis\RedisConnection;
use Cube\Modules\Redis\RedisConnector;

class RedisSessionStore implements SessionStoreInterface
{
    protected static string $prefix = 'cube_sessions';

    /**
     * Read session data by id
     *
     * @param string $id Session id
     * @return array
     */
    public function read(string $id): array
    {
        $data = static::getConnection()->command('GET', static::key($id));

        if (!$data) {
            return [];
        }

        $session = unserialize(
            base64_decode((string) $data)
        );

        return is_array($session) ? $session : [];
    }

    /**
     * Write session data by id
     *
     * @param string $id Session id
     * @param array $data Session data
     * @param int $lifetime Session lifetime in seconds
     * @return void
     */
    public function write(string $id, array $data, int $lifetime = 7200): void
    {
        static::getConnection()->command(
            'SETEX',
            static::key($id),
            $lifetime,
            base64_encode(serialize($data))
        );
    }

    /**
     * Destroy session by id
     *
     * @param string $id Session id
     * @return void
     */
    public function destroy(string $id): void
    {
        static::getConnection()->delete(static::key($id));
    }

    /**
     * Purge expired sessions
     *
     * Redis handles expiration with per-session TTLs.
     *
     * @param int $lifetime Session lifetime in seconds
     * @return void
     */
    public function purgeExpired(int $lifetime) {}

    /**
     * Get the configured Redis connection for sessions.
     *
     * @return RedisConnection
     */
    protected static function getConnection(): RedisConnection
    {
        return RedisConnector::connection(
            App::getConfig('app.session.redis_connection')
                ?: App::getConfig('app.session_redis_connection')
                ?: 'default'
        );
    }

    /**
     * Build a Redis key for a session.
     *
     * @param string $id Session id
     * @return string
     */
    protected static function key(string $id): string
    {
        $prefix = App::getConfig('app.session.redis_prefix')
            ?: App::getConfig('app.session_redis_prefix')
            ?: static::$prefix;

        return $prefix . ':' . $id;
    }
}
