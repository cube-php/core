<?php

namespace Cube\Modules\Redis;

use Cube\Exceptions\RedisException;
use Predis\Client as PredisClient;
use Redis;
use Throwable;

class RedisConnection
{
    /**
     * Create Redis connection wrapper.
     *
     * @param Redis|PredisClient $connection Redis client instance
     */
    public function __construct(protected Redis|PredisClient $connection) {}

    /**
     * Execute raw Redis command.
     *
     * @param string $command Redis command name
     * @param mixed ...$arguments Redis command arguments
     * @return mixed
     */
    public function command(string $command, mixed ...$arguments): mixed
    {
        try {
            if ($this->connection instanceof Redis) {
                return $this->connection->rawCommand($command, ...$arguments);
            }

            return $this->connection->executeRaw([
                $command,
                ...$arguments,
            ]);
        } catch (Throwable $e) {
            throw new RedisException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * Increment Redis key value.
     *
     * @param string $key Redis key
     * @return int
     */
    public function increment(string $key): int
    {
        return (int) $this->command('INCR', $key);
    }

    /**
     * Set Redis hash values.
     *
     * @param string $key Redis hash key
     * @param array $values Hash field values
     * @return void
     */
    public function hSet(string $key, array $values): void
    {
        $arguments = [$key];

        foreach ($values as $field => $value) {
            $arguments[] = $field;
            $arguments[] = (string) $value;
        }

        $this->command('HSET', ...$arguments);
    }

    /**
     * Get all Redis hash values.
     *
     * @param string $key Redis hash key
     * @return array
     */
    public function hGetAll(string $key): array
    {
        return $this->normalizeHash(
            $this->command('HGETALL', $key)
        );
    }

    /**
     * Add Redis sorted set member.
     *
     * @param string $key Redis sorted set key
     * @param int $score Sorted set score
     * @param string $member Sorted set member
     * @return void
     */
    public function zAdd(string $key, int $score, string $member): void
    {
        $this->command('ZADD', $key, $score, $member);
    }

    /**
     * Remove Redis sorted set member.
     *
     * @param string $key Redis sorted set key
     * @param string $member Sorted set member
     * @return void
     */
    public function zRem(string $key, string $member): void
    {
        $this->command('ZREM', $key, $member);
    }

    /**
     * Delete Redis key.
     *
     * @param string $key Redis key
     * @return void
     */
    public function delete(string $key): void
    {
        $this->command('DEL', $key);
    }

    /**
     * Execute Redis Lua script.
     *
     * @param string $script Lua script
     * @param array $keys Redis keys passed to the script
     * @param array $arguments Script arguments
     * @return mixed
     */
    public function eval(string $script, array $keys = [], array $arguments = []): mixed
    {
        return $this->command(
            'EVAL',
            $script,
            count($keys),
            ...$keys,
            ...array_map(fn($value) => (string) $value, $arguments)
        );
    }

    /**
     * Normalize Redis hash response into associative array.
     *
     * @param mixed $hash Redis hash response
     * @return array
     */
    protected function normalizeHash(mixed $hash): array
    {
        if (!is_array($hash)) {
            return [];
        }

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
}
