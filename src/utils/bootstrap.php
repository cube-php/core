<?php

$system_helpers_dir = __DIR__ . '/' . '..' . '/' . 'functions';

$require = function ($dir) {
    $helpers = scandir($dir);
    
    array_walk($helpers, function ($filename) use ($dir) {
        $filepath = $dir . '/' . $filename;
        $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

        if($extension === 'php') {
            require_once $filepath;
        }
    });
};

$require($system_helpers_dir);