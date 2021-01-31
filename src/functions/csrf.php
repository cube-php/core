<?php

use Cube\Tools\Csrf;

/**
 * Create csrf form input
 *
 * @return string
 */
function csrf_form() {
    $csrf = Csrf::get();
    return '<input type="hidden" name="csrf_token" value="'. $csrf .'"/>';
}

/**
 * Get current csrf token
 *
 * @return void
 */
function csrf_token() {
    return Csrf::get();
}

/**
 * Check if csrf token is valid
 *
 * @param string $token
 * @return bool
 */
function csrf($token) {
    return Csrf::isValid($token);
}