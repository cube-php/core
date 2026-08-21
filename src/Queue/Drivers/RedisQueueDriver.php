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

    /**
     * Push a job into the Redis-backed queue.
     *
     * @param JobsInterface $job
     * @param int $delay
     * @param bool $check_duplicate
     * @return void
     */
    public function push(
        JobsInterface $job,
        int $delay = 0,
        bool $check_duplicate = false
    ): void {
        $payload = serialize($job);
        $duplicate_key = static::duplicateKey($payload, $this->group);

        if (
            $this->shouldCheckDuplicate($check_duplicate) &&
            !$this->reserveDuplicate($duplicate_key, $payload, $this->group)
        ) {
            return;
        }

        $redis = static::getConnection();
        $id = $redis->increment(static::key('id'));
        $available_at = time() + $delay;

        $redis->hSet(static::jobKey($id), [
            'id' => $id,
            'group_name' => $this->group ?? '',
            'payload' => $payload,
            'attempts' => 0,
            'reserved_at' => '',
            'available_at' => $available_at,
        ]);

        if ($this->shouldCheckDuplicate($check_duplicate)) {
            static::getConnection()->command('SET', $duplicate_key, $id);
        }

        $redis->zAdd(static::key('available'), $available_at, (string) $id);
    }

    /**
     * Pop and reserve the next available Redis job.
     *
     * @return Job|null
     */
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

    /**
     * Release a reserved Redis job back onto the queue.
     *
     * @param Job $job
     * @param int $delay
     * @return void
     */
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

    /**
     * Delete a Redis job and its duplicate marker.
     *
     * @param Job $job
     * @return void
     */
    public function delete(Job $job): void
    {
        $data = static::getConnection()->hGetAll(static::jobKey($job->id));

        static::getConnection()->zRem(
            static::key('available'),
            (string) $job->id
        );

        static::getConnection()->delete(static::jobKey($job->id));

        if ($data) {
            static::getConnection()->delete(
                static::duplicateKey(
                    (string) ($data['payload'] ?? ''),
                    (string) ($data['group_name'] ?? '')
                )
            );
        }
    }

    /**
     * Get the count of pending Redis jobs.
     *
     * @return int
     */
    public function getPendingJobsCount(): int
    {
        $count = static::getConnection()->eval(
            static::pendingCountScript(),
            [static::key('available'), static::key('job')],
            [time(), $this->group ?? '']
        );

        return (int) $count;
    }

    /**
     * Find a Redis job by ID.
     *
     * @param int $id
     * @return Job|null
     */
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

    /**
     * Get the configured Redis connection.
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
     * Build a Redis key for the queue namespace.
     *
     * @param string $name
     * @return string
     */
    protected static function key(string $name): string
    {
        $prefix = App::getConfig('queue.redis_prefix') ?: static::$prefix;
        return $prefix . ':' . $name;
    }

    /**
     * Build a Redis job hash key.
     *
     * @param int|string $id
     * @return string
     */
    protected static function jobKey(int|string $id): string
    {
        return static::key('job') . ':' . $id;
    }

    /**
     * Build the duplicate marker key for a payload and group.
     *
     * @param string $payload
     * @param string|null $group
     * @return string
     */
    protected static function duplicateKey(string $payload, ?string $group): string
    {
        return static::key('duplicate') . ':' . hash(
            'sha256',
            ($group ?? '') . '|' . $payload
        );
    }

    /**
     * Determine if duplicate checks should run for this push.
     *
     * @param bool $check_duplicate
     * @return bool
     */
    protected function shouldCheckDuplicate(bool $check_duplicate): bool
    {
        return $check_duplicate || filter_var(
            App::getConfig('queue.check_duplicates'),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    /**
     * Reserve a duplicate marker before pushing the job.
     *
     * @param string $duplicate_key
     * @param string $payload
     * @param string|null $group
     * @return bool
     */
    protected function reserveDuplicate(
        string $duplicate_key,
        string $payload,
        ?string $group
    ): bool
    {
        if (
            $this->hasDuplicate($duplicate_key) ||
            $this->hasDuplicatePayload($payload, $group)
        ) {
            return false;
        }

        $result = static::getConnection()->command('SET', $duplicate_key, 'reserved', 'NX');

        return (bool) $result;
    }

    /**
     * Check if a duplicate marker points to an existing job.
     *
     * @param string $duplicate_key
     * @return bool
     */
    protected function hasDuplicate(string $duplicate_key): bool
    {
        $id = static::getConnection()->command('GET', $duplicate_key);

        if (!$id) {
            return false;
        }

        if ((string) $id === 'reserved') {
            return true;
        }

        if ($this->findJob((int) $id)) {
            return true;
        }

        static::getConnection()->delete($duplicate_key);
        return false;
    }

    /**
     * Check existing Redis job hashes for a matching payload and group.
     *
     * @param string $payload
     * @param string|null $group
     * @return bool
     */
    protected function hasDuplicatePayload(string $payload, ?string $group): bool
    {
        foreach (static::scanJobKeys() as $key) {
            $job = static::getConnection()->hGetAll($key);

            if (!$job) {
                continue;
            }

            if (
                (string) ($job['payload'] ?? '') === $payload &&
                (string) ($job['group_name'] ?? '') === ($group ?? '')
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Scan Redis for all queue job hash keys.
     *
     * @return array
     */
    protected static function scanJobKeys(): array
    {
        $cursor = '0';
        $keys = [];

        do {
            $result = static::getConnection()->command(
                'SCAN',
                $cursor,
                'MATCH',
                static::key('job') . ':*',
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

    /**
     * Normalize a Redis hash response into an associative array.
     *
     * @param array $hash
     * @return array
     */
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

    /**
     * Get the Lua script that atomically pops and reserves a job.
     *
     * @return string
     */
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

    /**
     * Get the Lua script that counts pending jobs for a group.
     *
     * @return string
     */
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
