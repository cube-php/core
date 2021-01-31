<?php

namespace Cube\Misc;

use InvalidArgumentException;

use Cube\Interfaces\CollectionInterface;

abstract class Collection implements CollectionInterface
{

    /**
     * Collection
     * 
     * @var array
     */
    private $collection = array();

    /**
     * Class constructor
     * 
     */
    public function __construct()
    {
        
    }

    /**
     * Get all items in the collection
     * 
     * @return array;
     */
    public function all() {

        $this->collection = array_change_key_case($this->collection, CASE_LOWER);
        return $this->collection;
    }

    /**
     * Return number of items in collection
     *
     * @return integer
     */
    public function count(): int {
        return count($this->collection);
    }
 
    /**
     * Remove all items from collection
     * 
     * 
     */
    public function clear() {

        unset($this->collection);
        $this->collection = array();
    }

    /**
     * Get the value of $key in collection
     * 
     * @param string|int $key Index to find
     * 
     * @return string|string[]
     */
    public function get($key)
    {
        return $this->collection[strtolower($key)] ?? null;
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
        return array_key_exists($name, $this->collection);
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
        
        if(!$this->has($name)) return;

        unset($this->collection[$name]);
        return $this->collection;
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
        if(!(is_string($name) || is_numeric($name))) {

            throw new InvalidArgumentException
                ('Collection field name shoud be a string or an integer');
        }

        $this->collection[strtolower($name)] = $value;
        return count($this->collection);
    }
}