<?php

namespace Cube\Misc;

use ArrayObject;
use InvalidArgumentException;
use Cube\Interfaces\CollectionInterface;

class Collection extends ArrayObject implements CollectionInterface
{
    /**
     * Get all items in the collection
     * 
     * @return mixed[];
     */
    public function all(): array
    {
        return array_change_key_case(
            $this->getArrayCopy()
        );
    }

    /**
     * CLear collection
     *
     * @return void
     */
    public function clear()
    {
        every($this->getArrayCopy(), function ($val, $key) {
            unset($this[$key]);
        });
    }

    /**
     * Run function all through items
     *
     * @param callable $fn
     * @return $this
     */
    public function each(callable $fn)
    {
        every(
            $this,
            fn ($value, $key) => $fn($value, $key, $this)
        );

        return $this;
    }

    /**
     * Find item in collection
     *
     * @param callable $fn
     * @return mixed
     */
    public function find(callable $fn)
    {
        return array_find($this, $fn);
    }

    /**
     * Find all items in collection
     *
     * @param callable $fn
     * @return mixed
     */
    public function findAll(callable $fn)
    {
        return array_find_all($this, $fn);
    }

    /**
     * Find index of item in collection
     *
     * @param callable $fn
     * @return int
     */
    public function findIndex(callable $fn): int
    {
        return array_find_index($this, $fn);
    }

    /**
     * Get the value of $key in collection
     * 
     * @param string|int $key Index to find
     * 
     * @return mixed[] | null
     */
    public function get($key)
    {
        return $this->all()[$key] ?? null;
    }

    /**
     * Check if key is in collection
     * 
     * @param string $name Collection field name to check
     * 
     * @return bool
     */
    public function has($name)
    {
        return array_key_exists($name, $this->all());
    }

    /**
     * Remove key from collection
     * 
     * @param string $name Collection field name to remove
     * 
     * @return array
     */
    public function remove($name)
    {
        $name = strtolower($name);

        if (!$this->has($name)) return;

        unset($this[$name]);
        return $this->all();
    }

    /**
     * Add item to collection
     * 
     * @param string|int $name Collection field name to add
     * @param string|string[] $value Value of collection field
     * 
     * @return int Total number of items in collection
     * 
     * @throws \InvalidArgumentException If $name is not a string or an integer
     */
    public function set($name, $value)
    {
        if (!(is_string($name) || is_numeric($name))) {

            throw new InvalidArgumentException('Collection field name shoud be a string or an integer');
        }

        $this[$name] = $value;
        return $this->count();
    }

    /**
     * Slice collection
     *
     * @param integer $offset
     * @param integer $length
     * @param boolean $preserve_keys
     * @return $this
     */
    public function slice(int $offset, int $length, bool $preserve_keys = false)
    {
        $cls = get_called_class();
        $array = array_slice(
            $this->getArrayCopy(),
            $offset,
            $length,
            $preserve_keys
        );

        return new $cls($array);
    }

    /**
     * Model collection splice
     *
     * @param integer $offset
     * @param integer|null $length
     * @param mixed $replacement
     * @return $this
     */
    public function splice(int $offset, ?int $length = null, mixed $replacement = [])
    {
        $cls = get_called_class();
        $array = array_splice(
            $this->getArrayCopy(),
            $offset,
            $length,
            $replacement
        );

        return new $cls($array);
    }

    /**
     * Create new instance
     *
     * @param array $array
     * @return self
     */
    public static function new(array $array): self
    {
        return new self($array);
    }
}
