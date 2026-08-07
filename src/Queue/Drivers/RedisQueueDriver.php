<?php

namespace Cube\Queue\Drivers;

use Cube\App\App;
use Cube\Interfaces\JobsInterface;
use Cube\Interfaces\QueueDriverInterface;
use Cube\Modules\Redis\RedisConnection;
use Cube\Modules\Redis\RedisConnector;
use Cube\Queue\Job;

class RedisQueueDriver implements QueueDriverInterface
{
    protected static string $prefix = 'cube_jobs';

    public function __construct(protected ?string $group = null) {}

    public function push(JobsInterface $job, int $delay = 0): void
    {
        $redis = static::getConnection();
        $id = $redis->increment(static::key('id'));
        $available_at = time() + $delay;

        $redis->hSet(static::jobKey($id), [
            'id' => $id,
            'group_name' => $this->group ?? '',
            'payload' => serialize($job),
            'attempts' => 0,
            'reserved_at' => '',
            'available_at' => $available_at,
        ]);

        $redis->zAdd(static::key('available'), $available_at, (string) $id);
    }

    public function pop(): ?Job
    {
        $result = static::getConnection()->eval(
            static::popScript(),
            [static::key('available'), static::key('job')],
            [time(), $this->group ?? '']
        );

        if (!$result) {
            return null;
        }

        $row = static::normalizeHash($result);

        if (!$row) {
            return null;
        }

        return new Job(
            (int) $row['id'],
            (string) $row['payload'],
            (int) $row['attempts']
        );
    }

    public function release(Job $job, int $delay = 0): void
    {
        $available_at = time() + $delay;

        static::getConnection()->hSet(static::jobKey($job->id), [
            'reserved_at' => '',
            'available_at' => $available_at
        ]);

        static::getConnection()->zAdd(
            static::key('available'),
            $available_at,
            (string) $job->id
        );
    }

    public function delete(Job $job): void
    {
        static::getConnection()->zRem(
            static::key('available'),
            (string) $job->id
        );

        static::getConnection()->delete(static::jobKey($job->id));
    }

    public function getPendingJobsCount(): int
    {
        $count = static::getConnection()->eval(
            static::pendingCountScript(),
            [static::key('available'), static::key('job')],
            [time(), $this->group ?? '']
        );

        return (int) $count;
    }

    public function findJob(int $id): ?Job
    {
        $result = static::getConnection()->hGetAll(static::jobKey($id));

        if (!$result) {
            return null;
        }

        return new Job(
            (int) $result['id'],
            (string) $result['payload'],
            (int) $result['attempts']
        );
    }

    protected static function getConnection(): RedisConnection
    {
        return RedisConnector::connection(
            App::getConfig('queue.redis_connection') ?: 'default'
        );
    }

    protected static function key(string $name): string
    {
        $prefix = App::getConfig('queue.redis_prefix') ?: static::$prefix;
        return $prefix . ':' . $name;
    }

    protected static function jobKey(int|string $id): string
    {
        return static::key('job') . ':' . $id;
    }

    protected static function normalizeHash(array $hash): array
    {
        $is_list = array_keys($hash) === range(0, count($hash) - 1);

        if (!$is_list) {
            return $hash;
        }

        $normalized = [];

        for ($i = 0; $i < count($hash); $i += 2) {
            if (!isset($hash[$i + 1])) {
                continue;
            }

            $normalized[$hash[$i]] = $hash[$i + 1];
        }

        return $normalized;
    }

    protected static function popScript(): string
    {
        return <<<'LUA'
local ids = redis.call('ZRANGEBYSCORE', KEYS[1], '-inf', ARGV[1], 'LIMIT', 0, 100)

for _, id in ipairs(ids) do
    local job_key = KEYS[2] .. ':' .. id
    local group_name = redis.call('HGET', job_key, 'group_name') or ''

    if ARGV[2] == '' or group_name == ARGV[2] then
        redis.call('ZREM', KEYS[1], id)
        redis.call('HINCRBY', job_key, 'attempts', 1)
        redis.call('HSET', job_key, 'reserved_at', ARGV[1])
        return redis.call('HGETALL', job_key)
    end
end

return nil
LUA;
    }

    protected static function pendingCountScript(): string
    {
        return <<<'LUA'
local ids = redis.call('ZRANGEBYSCORE', KEYS[1], '-inf', ARGV[1])

if ARGV[2] == '' then
    return #ids
end

local count = 0

for _, id in ipairs(ids) do
    local group_name = redis.call('HGET', KEYS[2] .. ':' .. id, 'group_name') or ''

    if group_name == ARGV[2] then
        count = count + 1
    end
end

return count
LUA;
    }
}
