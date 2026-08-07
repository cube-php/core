<?php

namespace Cube\Queue\Migrations;

use Cube\Interfaces\MigrationInterface;
use Cube\Queue\Drivers\QueueDriverFactory;
use InvalidArgumentException;

class JobsMigration implements MigrationInterface
{
    public static function up()
    {
        static::getMigration()::up();
    }

    public static function empty()
    {
        static::getMigration()::empty();
    }

    public static function down()
    {
        static::getMigration()::down();
    }

    protected static function getMigration(): string
    {
        return match (QueueDriverFactory::getDriver()) {
            QueueDriverFactory::DRIVER_REDIS => RedisJobsMigration::class,
            QueueDriverFactory::DRIVER_DATABASE => DatabaseJobsMigration::class,
            default => throw new InvalidArgumentException(
                'Unsupported queue driver "' . QueueDriverFactory::getDriver() . '"'
            ),
        };
    }
}
