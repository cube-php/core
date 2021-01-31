<?php
/**
 * String functions here
 * 
 * =============================================================
 * Methods related to strings should go here
 * =============================================================
 */

if(!function_exists('concat')) {
    /**
     * Concatenate string
     *
     * @param ...$args Arguments
     * @return string
     */
    function concat(...$args): string {
        return implode($args);
    }
}

if(!function_exists('str_starts_with')) {
    /**
     * String starts with
     *
     * @param string $needle
     * @param string $haystack
     * @return boolean
     */
    function str_starts_with($needle, $haystack): bool {
        return substr($haystack, 0, 1) === $needle;
    }
}

if(!function_exists('str_ends_with')) {
    /**
     * String ends with
     *
     * @param string $needle
     * @param string $haystack
     * @return boolean
     */
    function str_ends_with($needle, $haystack): bool {
        return substr($haystack, -1, 1) === $needle;
    }
}