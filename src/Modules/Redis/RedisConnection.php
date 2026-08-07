<?php

namespace Cube\Modules\Redis;

use Cube\Exceptions\RedisException;
use Predis\Client as PredisClient;
use Redis;
use Throwable;

class RedisConnection
{
    public function __construct(protected Redis|PredisClient $connection) {}

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

    public function increment(string $key): int
    {
        return (int) $this->command('INCR', $key);
    }

    public function hSet(string $key, array $values): void
    {
        $arguments = [$key];

        foreach ($values as $field => $value) {
            $arguments[] = $field;
            $arguments[] = (string) $value;
        }

        $this->command('HSET', ...$arguments);
    }

    public function hGetAll(string $key): array
    {
        return $this->normalizeHash(
            $this->command('HGETALL', $key)
        );
    }

    public function zAdd(string $key, int $score, string $member): void
    {
        $this->command('ZADD', $key, $score, $member);
    }

    public function zRem(string $key, string $member): void
    {
        $this->command('ZREM', $key, $member);
    }

    public function delete(string $key): void
    {
        $this->command('DEL', $key);
    }

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
