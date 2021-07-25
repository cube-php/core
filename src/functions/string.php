<?php
/**
 * String functions here
 * 
 * =============================================================
 * Methods related to strings should go here
 * =============================================================
 */

use Cube\Tools\Str;

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

if(!function_exists('is_email')) {
    /**
     * Check if str is an email
     *
     * @param string $email
     * @return boolean
     */
    function is_email(string $email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}

if(!function_exists('str')) {
    /**
     * Str class
     *
     * @param string $string
     * @return mixed
     */
    function str($string) {
        return new Str($string);
    }
}