<?php

use Cube\App;

App::registerPath();

$system_helpers_dir = __DIR__ . DS . '..' . DS . 'functions';
$defined_helpers_dir = APP_HELPERS_PATH;

$require = function ($dir) {
    $helpers = scandir($dir);
    
    array_walk($helpers, function ($filename) use ($dir) {
        $filepath = $dir . DIRECTORY_SEPARATOR . $filename;
        $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

        if($extension === 'php') {
            require_once $filepath;
        }
    });
};

$require($system_helpers_dir);
$require($defined_helpers_dir);