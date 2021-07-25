<?php

/**
 * Date functions here
 * 
 * =============================================================
 * Methods related to date and time should go here
 * =============================================================
 */

/**
 * Get the current timestamp
 *
 * @return string
 */
function getnow() {
    return gettime();
}

 /**
  * Get timestamp
  *
  * @param int|null $time
  * @return string
  */
function gettime($time = null) {
    $the_time = $time ?? time();
    return date('Y-m-d H:i:s', $the_time);
}

/**
 * get days in seconds
 *
 * @param string $days
 * @return integer
 */
function getdays(string $days): int {
    return ($days * 24 * 60 * 60);
}

/**
 * get minutes in seconds
 *
 * @param float $mins
 * @return float
 */
function getmins(float $mins): float {
    return ($mins * 60);
}