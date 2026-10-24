<?php

use Cube\Exceptions\RedisException;
use Cube\Exceptions\RedisConnectionException;
use Cube\Modules\Redis\RedisConnection;
use Predis\Client as PredisClient;
use Predis\Connection\ConnectionException;
use Predis\Connection\Parameters;
use Predis\Connection\StreamConnection;

class RedisErrorResponseClient extends PredisClient
{
    public function __construct() {}

    public function executeRaw(array $arguments, &$error = null)
    {
        $error = true;
        return 'NOPERM this user has no permissions to access the queue';
    }
}

class RedisReadFailureClient extends PredisClient
{
    public function __construct() {}

    public function executeRaw(array $arguments, &$error = null)
    {
        throw new ConnectionException(
            new StreamConnection(new Parameters([
                'host' => 'redis.test',
                'port' => 6379,
            ])),
            'Error while reading line from the server'
        );
    }
}

it('converts predis server errors into redis exceptions', function () {
    $connection = new RedisConnection(new RedisErrorResponseClient());

    expect(fn() => $connection->command('EVAL', 'return 1'))
        ->toThrow(
            RedisException::class,
            'NOPERM this user has no permissions to access the queue'
        );
});

it('identifies predis transport failures as redis connection exceptions', function () {
    $connection = new RedisConnection(new RedisReadFailureClient());

    expect(fn() => $connection->command('PING'))
        ->toThrow(
            RedisConnectionException::class,
            'Error while reading line from the server'
        );
});
