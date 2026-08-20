<?php

namespace Cube\Http\Session;

interface SessionStoreInterface
{
    public function read(string $id): array;
    public function write(string $id, array $data, int $lifetime = 7200): void;
    public function destroy(string $id): void;

    public function purgeExpired(int $lifetime);
}
