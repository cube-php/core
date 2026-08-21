<?php

namespace Cube\Queue\Migrations;

use Cube\App\App;
use Cube\Interfaces\MigrationInterface;
use Cube\Modules\Redis\RedisConnection;
use Cube\Modules\Redis\RedisConnector;

class RedisJobsMigration implements MigrationInterface
{
    protected static string $name = 'cube_jobs';

    /**
     * Verify the configured Redis connection for jobs.
     *
     * @return void
     */
    public static function up()
    {
        static::getConnection()->command('PING');
    }

    /**
     * Delete all Redis keys used by the jobs queue.
     *
     * @return void
     */
    public static function empty()
    {
        $prefix = App::getConfig('queue.redis_prefix') ?: static::$name;
        $connection = static::getConnection();

        $connection->delete($prefix . ':available');
        $connection->delete($prefix . ':id');

        foreach (static::scanKeys($prefix . ':job:*') as $key) {
            $connection->delete($key);
        }
    }

    /**
     * Delete all Redis keys used by the jobs queue.
     *
     * @return void
     */
    public static function down()
    {
        static::empty();
    }

    /**
     * Get the configured Redis connection for jobs.
     *
     * @return RedisConnection
     */
    protected static function getConnection(): RedisConnection
    {
        return RedisConnector::connection(
            App::getConfig('queue.redis_connection') ?: 'default'
        );
    }

    /**
     * Scan Redis keys matching the given pattern.
     *
     * @param string $pattern
     * @return array
     */
    protected static function scanKeys(string $pattern): array
    {
        $cursor = '0';
        $keys = [];

        do {
            $result = static::getConnection()->command(
                'SCAN',
                $cursor,
                'MATCH',
                $pattern,
                'COUNT',
                100
            );

            if (!is_array($result) || count($result) < 2) {
                break;
            }

            $cursor = (string) $result[0];
            $keys = array_merge($keys, $result[1]);
        } while ($cursor !== '0');

        return $keys;
    }
}
