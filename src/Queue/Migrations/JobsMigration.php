<?php

namespace Cube\Queue\Migrations;

use Cube\Interfaces\MigrationInterface;
use Cube\Queue\Drivers\QueueDriverFactory;
use InvalidArgumentException;

class JobsMigration implements MigrationInterface
{
    /**
     * Run the configured jobs migration.
     *
     * @return void
     */
    public static function up()
    {
        static::getMigration()::up();
    }

    /**
     * Empty the configured jobs storage.
     *
     * @return void
     */
    public static function empty()
    {
        static::getMigration()::empty();
    }

    /**
     * Roll back the configured jobs storage.
     *
     * @return void
     */
    public static function down()
    {
        static::getMigration()::down();
    }

    /**
     * Get the migration class for the configured queue driver.
     *
     * @return string
     */
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
