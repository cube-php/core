<?php

namespace Cube\Http\Session;

class SessionHandler
{
    protected bool $changed = false;

    public function __construct(protected string $id, protected array $data = []) {}

    /**
     * Get session id
     *
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Get session value by key
     *
     * @param string $key Session key name
     * @param mixed $default Default value if key does not exist
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Set session value by key
     *
     * @param string $key Session key name
     * @param mixed $value Session value
     * @return void
     */
    public function put(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
        $this->changed = true;
    }

    /**
     * Check if session has a key
     *
     * @param string $key Session key name
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Get session value and remove it
     *
     * @param string $key Session key name
     * @param mixed $default Default value if key does not exist
     * @return mixed
     */
    public function remove(string $key): void
    {
        unset($this->data[$key]);
        $this->changed = true;
    }

    /**
     * Get all session data
     *
     * @return array
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Check if session data has been changed
     *
     * @return bool
     */
    public function isChanged(): bool
    {
        return $this->changed;
    }
}
