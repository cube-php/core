<?php

namespace Cube\Traits;

trait Onceable
{
    protected $onceable_caches = array();

    /**
     * Call and cache $callable
     *
     * @param callable $callable
     * @return mixed
     */
    public function once($callable)
    {
        $class = get_called_class();
        $method_name = get_called_class_method();
        $key = concat($class, '::', $method_name);

        if(isset($this->onceable_caches[$key])) {
            return $this->onceable_caches[$key];
        }

        $res = $callable();
        $this->onceable_caches[$key] = $res;
        return $res;
    }
}