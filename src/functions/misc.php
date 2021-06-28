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
 * @param mixed ...$data
 * @return void
 */
function dd(...$data) {
    $backtrace = debug_backtrace();
    $exact_location = $backtrace[0]['file'];
    $exact_line = $backtrace[0]['line'];

    $styles_list = array(
        'color' => 'red',
        'font-size' => '12px',
        'font-weight' => 'bold',
        'border' => '1px solid red',
        'padding' => '8px'
    );

    $styles = every($styles_list, function ($value, $attr) {
        return concat($attr, ':', $value);
    });

    $header_content = concat($exact_location, ' on line ', $exact_line);
    $header = h('div', ['style' => implode(';', $styles)], $header_content);

    every($data, function ($pdata) use ($header) {
        echo $header;
        var_dump($pdata);
    });

    die();
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

/**
 * Recursively delete files in a directory
 *
 * @param string $dir
 * @return integer
 */
function unlink_dir_files(string $dir): int {
    $files = scandir($dir);
    $dot_files = array('.', '..');
    $count = 0;

    every($files, function ($file) use ($dot_files, $dir, &$count) {

        if(in_array($file, $dot_files)) {
            return;
        }

        $file_path = concat($dir, DIRECTORY_SEPARATOR, $file);

        if(is_dir($file_path)) {
            unlink_dir_files($file_path);
            rmdir($file_path);
            return $count++;
        }

        if(unlink($file_path)) {
            $count++;
        }
    });

    return $count;
}