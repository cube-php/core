<?php

if(!function_exists('every')) {
    /**
     * Iterate over every array and call $func
     *
     * @param iterable $arr
     * @param callable $func
     * @return array
     */
    function every(iterable $arr, callable $func): array {
        $result = [];

        array_walk($arr, function ($value, $index) use (&$result, $func) {
            $result[$index] = $func($value);
        });

        return $result;
    }
}