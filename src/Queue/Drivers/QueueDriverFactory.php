<?php

namespace Cube\Queue\Drivers;

use Cube\App\App;
use Cube\Interfaces\QueueDriverInterface;
use InvalidArgumentException;

class QueueDriverFactory
{
    public const DRIVER_DATABASE = 'database';

    public const DRIVER_REDIS = 'redis';

    /**
     * Create the configured queue driver.
     *
     * @param string|null $group
     * @return QueueDriverInterface
     */
    public static function make(?string $group = null, array $groups = [], array $excludedGroups = []): QueueDriverInterface
    {
        return match (static::getDriver()) {
            static::DRIVER_REDIS => new RedisQueueDriver($group, $groups, $excludedGroups),
            static::DRIVER_DATABASE => new DatabaseQueueDriver($group, $groups, $excludedGroups),
            default => throw new InvalidArgumentException(
                'Unsupported queue driver "' . static::getDriver() . '"'
            ),
        };
    }

    /**
     * Get the configured queue driver name.
     *
     * @return string
     */
    public static function getDriver(): string
    {
        return strtolower((string) (App::getConfig('queue.driver') ?: static::DRIVER_DATABASE));
    }
}
