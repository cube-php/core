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