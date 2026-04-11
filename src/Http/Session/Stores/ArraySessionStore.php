<?php

namespace Cube\Http\Session\Stores;

use Cube\Http\Session\SessionStoreInterface;

class ArraySessionStore implements SessionStoreInterface
{
    protected array $sessions = [];

    public function __construct() {}

    /**
     * Read session data by id
     *
     * @param string $id Session id
     * @return array
     */
    public function read(string $id): array
    {
        return $this->sessions[$id] ?? [];
    }

    /**
     * Write session data by id
     *
     * @param string $id Session id
     * @param array $data Session data
     * @return void
     */
    public function write(string $id, array $data): void
    {
        $this->sessions[$id] = $data;
    }

    /**
     * Destroy session by id
     *
     * @param string $id Session id
     * @return void
     */
    public function destroy(string $id): void
    {
        unset($this->sessions[$id]);
    }

    /**
     * Purge expired sessions
     *
     * @param int $lifetime Session lifetime in seconds
     * @return void
     */
    public function purgeExpired(int $lifetime) {}
}
