<?php

namespace Cube\Modules\Redis;

use Cube\App\App;
use Cube\Exceptions\RedisException;
use Predis\Client as PredisClient;
use Redis;
use Throwable;

class RedisConnector
{
    public const DEFAULT_CONNECTION_NAME = 'default';

    protected static array $connections = array();

    public static function connection(string $name = self::DEFAULT_CONNECTION_NAME): RedisConnection
    {
        if (isset(static::$connections[$name])) {
            return static::$connections[$name];
        }

        $options = static::getOptions($name);

        if (class_exists(Redis::class)) {
            return static::$connections[$name] = static::connectWithExtension($options);
        }

        if (class_exists(PredisClient::class)) {
            return static::$connections[$name] = static::connectWithPredis($options);
        }

        throw new RedisException(
            'Redis jobs require the PHP redis extension or predis/predis.'
        );
    }

    public static function reconnect(string $name = self::DEFAULT_CONNECTION_NAME): RedisConnection
    {
        unset(static::$connections[$name]);
        return static::connection($name);
    }

    public static function closeAll(): void
    {
        foreach (static::$connections as $key => $connection) {
            unset(static::$connections[$key]);
        }
    }

    protected static function getOptions(string $name): array
    {
        $config = App::getConfig('redis', []);
        $options = is_array($config) ? ($config[$name] ?? []) : [];

        return [
            'host' => static::option($options, 'host', fn() => env('redis_host', '127.0.0.1')),
            'port' => (int) static::option($options, 'port', fn() => env('redis_port', 6379)),
            'password' => static::option($options, 'password', fn() => env('redis_password')),
            'database' => (int) static::option($options, 'database', fn() => env('redis_database', 0)),
            'timeout' => (float) static::option($options, 'timeout', fn() => env('redis_timeout', 5.0)),
        ];
    }

    protected static function option(array $options, string $name, callable $default): mixed
    {
        return array_key_exists($name, $options) ? $options[$name] : $default();
    }

    protected static function connectWithExtension(array $options): RedisConnection
    {
        $redis = new Redis();

        try {
            $redis->connect(
                $options['host'],
                (int) $options['port'],
                (float) ($options['timeout'] ?? 0.0)
            );

            if (!empty($options['password'])) {
                $redis->auth($options['password']);
            }

            if (isset($options['database'])) {
                $redis->select((int) $options['database']);
            }
        } catch (Throwable $e) {
            throw new RedisException($e->getMessage(), (int) $e->getCode(), $e);
        }

        return new RedisConnection($redis);
    }

    protected static function connectWithPredis(array $options): RedisConnection
    {
        $parameters = [
            'scheme' => 'tcp',
            'host' => $options['host'],
            'port' => (int) $options['port'],
            'database' => (int) ($options['database'] ?? 0),
        ];

        if (!empty($options['password'])) {
            $parameters['password'] = $options['password'];
        }

        if (isset($options['timeout']) && (float) $options['timeout'] > 0) {
            $parameters['timeout'] = (float) $options['timeout'];
        }

        return new RedisConnection(new PredisClient($parameters));
    }
}
