<?php

namespace Cube\Tools;

use Cube\App\App;
use Cube\Modules\Redis\RedisConnector;
use InvalidArgumentException;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class Cache
{
    public const DRIVER_FILE = 'file';

    public const DRIVER_FILESYSTEM = 'filesystem';

    public const DRIVER_REDIS = 'redis';

    protected static (AdapterInterface&CacheInterface)|null $adapter = null;

    /**
     * Set cache
     *
     * @param string $name
     * @param integer $expires_after
     * @param callable $fn
     * @return mixed
     */
    public static function getOrSet(string $name, int $expires_after, callable $fn)
    {
        return self::getAdapter()->get($name, function (ItemInterface $item) use ($expires_after, $fn) {
            $item->expiresAfter($expires_after);
            return $fn();
        });
    }

    /**
     * Get cache
     *
     * @param string $name
     * @return mixed
     */
    public static function get(string $name)
    {
        return self::getAdapter()->getItem($name);
    }

    /**
     * Remove Cache
     *
     * @param string $name
     * @return bool
     */
    public static function delete(string $name)
    {
        return self::getAdapter()->deleteItem($name);
    }

    /**
     * Update cache
     *
     * @param string $name
     * @param mixed $data
     * @return bool
     */
    public static function update(string $name, mixed $data)
    {
        $cached_item = static::get($name);
        $cached_item->set($data);

        return self::getAdapter()->save($cached_item);
    }

    /**
     * Get adapter
     *
     * @return AdapterInterface&CacheInterface
     */
    protected static function getAdapter(): AdapterInterface&CacheInterface
    {
        if (static::$adapter) {
            return static::$adapter;
        }

        $driver = strtolower((string) (
            App::getConfig('cache.driver') ?: static::DRIVER_FILE
        ));

        return static::$adapter = match ($driver) {
            static::DRIVER_FILE,
            static::DRIVER_FILESYSTEM => new FilesystemAdapter(),
            static::DRIVER_REDIS => static::getRedisAdapter(),
            default => throw new InvalidArgumentException(
                'Unsupported cache driver "' . $driver . '"'
            ),
        };
    }

    /**
     * Create the configured Redis cache adapter.
     *
     * @return RedisAdapter
     */
    protected static function getRedisAdapter(): RedisAdapter
    {
        $connection = (string) (
            App::getConfig('cache.redis.connection')
            ?: RedisConnector::DEFAULT_CONNECTION_NAME
        );
        $prefix = (string) (
            App::getConfig('cache.redis.prefix') ?: 'cube_cache'
        );

        return new RedisAdapter(
            RedisConnector::connection($connection)->client(),
            $prefix
        );
    }
}
