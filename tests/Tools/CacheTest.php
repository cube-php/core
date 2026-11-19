<?php

use Cube\App\App;
use Cube\Modules\Redis\RedisConnector;
use Cube\Tools\Cache;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class ConfigurableCache extends Cache
{
    public static function adapter(): AdapterInterface
    {
        return parent::getAdapter();
    }
}

function bindCacheConfig(array $cache): void
{
    $app = (new ReflectionClass(App::class))->newInstanceWithoutConstructor();
    $caches = new ReflectionProperty(App::class, 'caches');
    $caches->setValue($app, [
        'config' => [
            'cache' => $cache,
            'redis' => [
                'cache' => [
                    'host' => 'redis.test',
                    'port' => 6379,
                    'username' => 'cube',
                    'password' => 'secret',
                    'database' => 3,
                    'timeout' => 5.0,
                ],
            ],
        ],
    ]);

    app()->singleton(App::class, fn() => $app);
    $instances = new ReflectionProperty(app(), 'singletonInstances');
    $values = $instances->getValue(app());
    $values[App::class] = $app;
    $instances->setValue(app(), $values);

    $adapter = new ReflectionProperty(Cache::class, 'adapter');
    $adapter->setValue(null, null);
    RedisConnector::closeAll();
}

it('uses the filesystem cache driver by default', function () {
    bindCacheConfig([]);

    expect(ConfigurableCache::adapter())->toBeInstanceOf(FilesystemAdapter::class);
});

it('creates a redis cache adapter from the configured connection and prefix', function () {
    bindCacheConfig([
        'driver' => 'redis',
        'redis' => [
            'connection' => 'cache',
            'prefix' => 'cube_cache',
        ],
    ]);

    $adapter = ConfigurableCache::adapter();
    $namespace = new ReflectionProperty(AbstractAdapter::class, 'namespace');

    expect($adapter)->toBeInstanceOf(RedisAdapter::class)
        ->and($namespace->getValue($adapter))->toBe('cube_cache:');
});

it('rejects unsupported cache drivers', function () {
    bindCacheConfig(['driver' => 'missing']);

    expect(fn() => ConfigurableCache::adapter())
        ->toThrow(InvalidArgumentException::class, 'Unsupported cache driver "missing"');
});
