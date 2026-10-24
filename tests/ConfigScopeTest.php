<?php

use Cube\App\App;
use Cube\Http\Session\SessionManagerFactory;
use Cube\Http\Session\Stores\RedisSessionStore;
use Cube\Modules\Redis\RedisConnector;
use Cube\Queue\Drivers\DatabaseQueueDriver;
use Cube\Queue\Drivers\RedisQueueDriver;

class ConfigScopeDatabaseQueueDriver extends DatabaseQueueDriver
{
    public static function connectionName(): string
    {
        return parent::getConnectionName();
    }
}

class ConfigScopeRedisQueueDriver extends RedisQueueDriver
{
    public static function configuredKey(string $name): string
    {
        return parent::key($name);
    }
}

class ConfigScopeRedisSessionStore extends RedisSessionStore
{
    public static function configuredKey(string $id): string
    {
        return parent::key($id);
    }
}

class ConfigScopeRedisConnector extends RedisConnector
{
    public static function options(string $name): array
    {
        return parent::getOptions($name);
    }

    public static function authentication(array $options): string|array|null
    {
        return parent::getAuthentication($options);
    }

    public static function predisParameters(array $options): array
    {
        return parent::getPredisParameters($options);
    }
}

/**
 * Bind scoped app and queue configuration for consumer tests.
 */
function bindScopedConfig(): void
{
    $app = (new ReflectionClass(App::class))->newInstanceWithoutConstructor();
    $caches = new ReflectionProperty(App::class, 'caches');
    $caches->setValue($app, [
        'config' => [
            'app' => [
                'session' => [
                    'handler' => 'redis',
                    'redis' => [
                        'connection' => 'session-cache',
                        'prefix' => 'test-sessions',
                    ],
                ],
            ],
            'queue' => [
                'database' => [
                    'connection' => 'queue-database',
                ],
                'redis' => [
                    'connection' => 'queue-cache',
                    'prefix' => 'test-jobs',
                ],
            ],
            'redis' => [
                'default' => [
                    'host' => 'redis.test',
                    'port' => 6379,
                    'username' => 'cube',
                    'password' => 'secret',
                    'database' => 2,
                    'timeout' => 3.5,
                ],
            ],
        ],
    ]);

    app()->singleton(App::class, fn() => $app);
    $instances = new ReflectionProperty(app(), 'singletonInstances');
    $values = $instances->getValue(app());
    $values[App::class] = $app;
    $instances->setValue(app(), $values);
}

it('reads session settings from their scoped configuration', function () {
    bindScopedConfig();

    expect(SessionManagerFactory::getStore())->toBe('redis')
        ->and(App::getConfig('app.session.redis.connection'))->toBe('session-cache')
        ->and(ConfigScopeRedisSessionStore::configuredKey('abc'))->toBe('test-sessions:abc');
});

it('reads queue settings from database and redis scopes', function () {
    bindScopedConfig();

    expect(ConfigScopeDatabaseQueueDriver::connectionName())->toBe('queue-database')
        ->and(App::getConfig('queue.redis.connection'))->toBe('queue-cache')
        ->and(ConfigScopeRedisQueueDriver::configuredKey('available'))->toBe('test-jobs:available');
});

it('builds redis username and password authentication pairs', function () {
    bindScopedConfig();
    $options = ConfigScopeRedisConnector::options('default');

    expect($options['username'])->toBe('cube')
        ->and($options['password'])->toBe('secret')
        ->and(ConfigScopeRedisConnector::authentication($options))->toBe(['cube', 'secret'])
        ->and(ConfigScopeRedisConnector::predisParameters($options))
        ->toMatchArray(['username' => 'cube', 'password' => 'secret'])
        ->and(ConfigScopeRedisConnector::authentication([
            'password' => 'legacy-secret',
        ]))->toBe('legacy-secret');
});
