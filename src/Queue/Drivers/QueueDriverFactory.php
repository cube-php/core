<?php

namespace Cube\Queue\Drivers;

use Cube\App\App;
use Cube\Interfaces\QueueDriverInterface;
use InvalidArgumentException;

class QueueDriverFactory
{
    public const DRIVER_DATABASE = 'database';

    public const DRIVER_REDIS = 'redis';

    public static function make(?string $group = null): QueueDriverInterface
    {
        return match (static::getDriver()) {
            static::DRIVER_REDIS => new RedisQueueDriver($group),
            static::DRIVER_DATABASE => new DatabaseQueueDriver($group),
            default => throw new InvalidArgumentException(
                'Unsupported queue driver "' . static::getDriver() . '"'
            ),
        };
    }

    public static function getDriver(): string
    {
        return strtolower((string) (App::getConfig('queue.driver') ?: static::DRIVER_DATABASE));
    }
}
