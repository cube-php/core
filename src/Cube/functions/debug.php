<?php

if(!function_exists('dd')) {

    /**
     * Die and dump
     *
     * @param mixed $var
     * @return void
     */
    function dd($var) {
        var_dump($var);
        nl2br(debug_print_backtrace());
        die();
    }
}