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