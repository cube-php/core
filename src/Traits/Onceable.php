<?php

namespace Cube\Traits;

trait Onceable
{
    protected $onceable_caches = array();

    /**
     * Call and cache $callable
     *
     * @param callable $callable
     * @param string|null $name Key name
     * @return mixed
     */
    public function once($callable, ?string $name = null)
    {
        $class = get_called_class();
        $method_name = $name ?: get_called_class_method();
        $key = concat($class, '::', $method_name);

        if (array_key_exists($key, $this->onceable_caches)) {
            return $this->onceable_caches[$key];
        }

        $res = $callable();
        $this->onceable_caches[$key] = $res;
        return $res;
    }
}
