<?php

namespace Cube\Tools;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

class Cache
{
    protected static $adapter;

    /**
     * Set cache
     *
     * @param string $name
     * @param integer $expires_after
     * @param callable $fn
     * @return mixed
     */
    public static function getOrSet(string $name, int $expires_after, callable $fn)
    {
        return self::getAdapter()->get($name, function (ItemInterface $item) use ($expires_after, $fn) {
            $item->expiresAfter($expires_after);
            return $fn();
        });
    }

    /**
     * Get cache
     *
     * @param string $name
     * @return mixed
     */
    public static function get(string $name)
    {
        return self::getAdapter()->getItem($name);
    }

    /**
     * Remove Cache
     *
     * @param string $name
     * @return bool
     */
    public static function delete(string $name)
    {
        return self::getAdapter()->deleteItem($name);
    }

    /**
     * Update cache
     *
     * @param string $name
     * @param mixed $data
     * @return bool
     */
    public static function update(string $name, mixed $data)
    {
        $cached_item = static::get($name);
        $cached_item->set($data);

        return self::getAdapter()->save($cached_item);
    }

    /**
     * Get adapter
     *
     * @return FileSystemAdapter
     */
    protected static function getAdapter()
    {
        self::$adapter ??= new FilesystemAdapter();
        return self::$adapter;
    }
}