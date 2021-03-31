<?php

/**
 * Miscellaenous functions here
 * 
 * =============================================================
 * Unrelated functions should go here
 * =============================================================
 */

/**
 * Generate random string token
 *
 * @param int $length
 * @return string
 */
function generate_token($length) {
    return bin2hex(openssl_random_pseudo_bytes($length));
}

/**
 * Die Dump
 *
 * @param mixed $data
 * @param boolean $should_die
 * @return void
 */
function dd($data, bool $should_die = true) {
    var_dump($data);
    if($should_die) die();
}

/**
 * Get method called in class
 *
 * @return string
 */
function get_called_class_method(): ?string {
   $backtrace = debug_backtrace(
       DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT,
       3
    );

    $caller = $backtrace[2] ?? null;

    if(!$caller) {
        return null;
    }

    return $caller['function'] ?? null;
}