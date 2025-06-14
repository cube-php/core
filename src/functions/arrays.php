<?php

/**
 * Array functions here
 * 
 * ==============================================
 * Arrays focused methods will and should go here
 * ==============================================
 */

if (!function_exists('array_get_first')) {
    /**
     * Get the first element of an array
     *
     * @param array $arr
     * @return mixed
     */
    function array_get_first(array $arr)
    {
        return $arr[0] ?? null;
    }
}

if (!function_exists('array_get_last')) {
    /**
     * Get the last element of an array
     *
     * @param array $arr
     * @return mixed
     */
    function array_get_last(array $arr)
    {
        $index = count($arr) - 1;
        return $arr[$index] ?? null;
    }
}

if (function_exists('array_shuffle')) {
    /**
     * Shuffle array
     *
     * @param array $arr
     * @return array
     */
    function array_shuffle(array $arr)
    {
        shuffle($arr);
        return $arr;
    }
}

if (!function_exists('array_wrap')) {
    /**
     * Wrap value as array if not an array
     * Else return the value itself
     *
     * @param mixed $var
     * @return array
     */
    function array_wrap($var)
    {
        if (!is_array($var)) {
            return [$var];
        }
        return $var;
    }
}

if (!function_exists('array_map_class')) {
    /**
     * Map array to class
     *
     * @param array $array Array to map
     * @param string $key Key
     * @param string $class Class name to map
     * @return array
     */
    function array_map_class(array $array, $key, $class)
    {
        return array_map(function ($item) use ($key, $class) {
            $item = (object) $item;
            return new $class($item->{$key});
        }, $array);
    }
}

if (!function_exists('every')) {
    /**
     * Iterate over every array and call $func
     *
     * @param array|ArrayObject $arr
     * @param callable $func
     * @return array
     */
    function every(array|ArrayObject $arr, callable $func): array
    {
        $result = [];
        $collection = ($arr instanceof ArrayObject)
            ? $arr->getArrayCopy()
            : $arr;

        array_walk($collection, function ($value, $index) use (&$result, $func) {
            $result[$index] = $func($value, $index);
        });

        return $result;
    }
}

if (!function_exists('array_until')) {
    /**
     * Loop through array until $fn is satisfied
     *
     * @param array $iterable
     * @param callable $fn
     * @return mixed
     */
    function array_until(array $iterable, callable $fn)
    {
        $result = null;
        foreach ($iterable as $index => $item) {
            if ($fn($item, $index)) {
                $result = $item;
                break;
            }
        }

        return $result;
    }
}

if (!function_exists('array_find_index')) {
    /**
     * Find array index
     *
     * @param iterable $arr
     * @param callable $func
     * @return mixed
     */
    function array_find_index(iterable $arr, callable $func)
    {
        foreach ($arr as $index => $value) {
            if ($func($value, $index)) {
                return $index;
            }
        }
    }
}

if (!function_exists('array_find')) {
    /**
     * Find an array when $func is true
     *
     * @param iterable $arr
     * @param callable $func
     * @return mixed
     */
    function array_find(iterable $arr, callable $func)
    {
        foreach ($arr as $index => $value) {
            if ($func($value, $index)) {
                return $value;
            }
        }
    }
}

if (!function_exists('array_find_all')) {
    /**
     * Find all instances where $func is true
     *
     * @param iterable $arr
     * @param callable $func
     * @return array
     */
    function array_find_all(iterable $arr, callable $func): array
    {
        $result = [];

        foreach ($arr as $index => $value) {
            if ($func($value, $index)) {
                $result[$index] = $value;
            }
        }

        return $result;
    }
}

if (!function_exists('array_prepend_all')) {
    /**
     * Prepend all items in an array with a value
     *
     * @param array $arr
     * @param mixed $value
     * @return array
     */
    function array_prepend_all(array $arr, $value): array
    {
        return array_map(function ($item) use ($value) {
            return $value . $item;
        }, $arr);
    }
}
