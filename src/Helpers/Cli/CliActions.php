<?php

namespace Cube\Helpers\Cli;

use Cube\App;
use Cube\Misc\File;
use Cube\Exceptions\FileSystemException;
use Cube\Modules\System;

class CliActions
{
    public static function buildAssetAction($options)
    {
        $name = $options['n'];

        if(!$name) {
            Cli::respondError('No name specified for CSS asset', true);
        }

        return static::buildAsset($options['t'], $name);
    }
    
    public static function buildController($options)
    {
        $name = $options['n'];

        if(!$name) {
            Cli::respondError('No name specified for controller', true);
        }

        $name = self::getSyntaxedName($name, 'Controller');

        $filename = self::addExt($name);
        $template = self::getReservedTemplate('controller');
        $model_path = APP_CONTROLLERS_PATH . DS . $filename;
        $refined_template = strtr($template, [
            '{className}' => self::getClassName($name),
            '{subNamespace}' => self::getClassNamespace($name)
        ]);

        try {
            $file = new File($model_path, true, true);
            $file->write($refined_template);
            Cli::respond('created controller: ' . $filename);
        }
        catch(FileSystemException $e) {
            Cli::respondError($e->getMessage());
            Cli::respondError('create controller failed', true);
        }
    }

    public static function buildException($options)
    {
        $name = $options['n'];

        if(!$name) {
            Cli::respondError('No name specified for exception', true);
        }

        $name = self::getSyntaxedName($name, 'Exception');

        $filename = self::addExt($name);
        $template = self::getReservedTemplate('exception');
        $exception_path = APP_EXCEPTIONS_PATH . DS . $filename;
        $refined_template = strtr($template, [
            '{className}' => self::getClassName($name),
            '{subNamespace}' => self::getClassNamespace($name)
        ]);

        try {
            $file = new File($exception_path, true, true);
            $file->write($refined_template);
            Cli::respond('created exception: ' . $filename);
        }
        catch(FileSystemException $e) {
            Cli::respondError($e->getMessage());
            Cli::respondError('create exception failed', true);
        }
    }

    public static function buildHelp()
    {
        $template = self::getReservedTemplate('help');
        Cli::respond($template, true);
    }

    public static function buildHelper($options)
    {
        $raw_name = $options['n'];

        if(!$raw_name) {
            Cli::respondError('No name specified for helper', true);
        }

        $name = strtolower($raw_name);

        $filename = self::addExt($name, false);
        $template = self::getReservedTemplate('helper');
        $helpers_path = APP_HELPERS_PATH . DS . $filename;
        $refined_template = strtr($template, ['{fn}' => $name]);

        try {
            $file = new File($helpers_path, true, true);
            $file->write($refined_template);
            Cli::respond('created helper: ' . $filename);
        }
        catch(FileSystemException $e) {
            Cli::respondError($e->getMessage());
            Cli::respondError('unable to create helper ' . $filename, true);
        }
    }

    public static function buildMiddleware($options)
    {

        $name = $options['n'];

        if(!$name) {
            Cli::respondError('No name specified for middleware', true);
        }

        $name = self::getSyntaxedName($name, 'Middleware');

        $filename = self::addExt($name);
        $template = self::getReservedTemplate('middleware');
        $middleware_path = APP_MIDDLEWARES_PATH . DS . $filename;
        $refined_template = strtr($template, [
            '{className}' => self::getClassName($name),
            '{subNamespace}' => self::getClassNamespace($name)
        ]);

        try {
            $file = new File($middleware_path, true, true);
            $file->write($refined_template);
            Cli::respond('created middleware: ' . $filename);
        }
        catch(FileSystemException $e) {
            Cli::respondError($e->getMessage());
            Cli::respondError(Cli::COMMAND_MIDDLEWARE . ' failed', true);
        }
    }

    public static function buildModel($options)
    {

        $table_name = $name = $options['n'];

        if(!$name) {
            Cli::respondError('No name specified for model', true);
        }

        $name = self::getSyntaxedName($name, 'Model');

        $filename = self::addExt($name);
        $template = self::getReservedTemplate('model');
        $model_path = APP_MODELS_PATH . DS . $filename;
        $refined_template = strtr($template, [
            '{tableName}' => $table_name,
            '{className}' => self::getClassName($name),
            '{subNamespace}' => self::getClassNamespace($name)
        ]);

        try {
            $file = new File($model_path, true, true);
            $file->write($refined_template);
            Cli::respond('created model: ' . $filename);
        }
        catch(FileSystemException $e) {
            Cli::respondError($e->getMessage());
            Cli::respondError(Cli::COMMAND_MODEL . ' failed', true);
        }
    }

    public static function buildProvider($options)
    {

        $name = $options['n'];
        Cli::respondWarning('Do not use providers');
    }

    public static function buildEvent($options)
    {

        $name = $options['n'];

        if(!$name) {
            Cli::respondError('No name specified for event', true);
        }

        $name = self::getSyntaxedName($name, 'Event');

        $filename = static::addExt($name);
        $template = static::getReservedTemplate('event');
        $model_path = APP_EVENTS_PATH . DS . $filename;
        $refined_template = strtr($template, [
            '{className}' => static::getClassName($name),
            '{subNamespace}' => static::getClassNamespace($name)
        ]);

        try {
            $file = new File($model_path, true, true);
            $file->write($refined_template);
            Cli::respond('created event: ' . $filename);
        }
        catch(FileSystemException $e) {
            Cli::respondError($e->getMessage());
            Cli::respond(Cli::COMMAND_EVENT . ' failed', true);
        }
    }

    public static function buildMigration($options)
    {

        $name = $main_name = $options['n'];

        if(!$name) {
            Cli::respondError('No name specified for migration', true);
        }

        $name = self::getSyntaxedName($name);

        $filename = static::addExt($name);
        $template = static::getReservedTemplate('migration');
        $model_path = APP_MIGRATIONS_PATH . DS . $filename;
        $refined_template = strtr($template, [
            '{className}' => static::getClassName($name),
            '{name}' => strtolower($main_name)
        ]);

        try {
            $file = new File($model_path, true, true);
            $file->write($refined_template);
            Cli::respond('created migration: ' . $filename);
        }
        catch(FileSystemException $e) {
            Cli::respondError($e->getMessage());
            Cli::respond(Cli::COMMAND_MIGRATION . ' failed', true);
        }
    }

    public static function serve($options)
    {
        $port = $options['p'] ?? 8888;

        if(preg_match("/[^0-9]/", $port)) {
            return Cli::respondError('Invalid port number: ' .$port, true);
        }

        $get_local_ip = function () {
        
            $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            socket_connect($sock, "8.8.8.8", 53);
            socket_getsockname($sock, $name);
            
            return $name;
        };

        $keys = array_keys($options);
        $host = in_array('w', $keys) ? $get_local_ip() : '127.0.0.1';
        
        $url = "{$host}:{$port}";
        $dir = APP_WEBROOT_PATH;

        Cli::respondSuccess("Hosting app on: http://{$url}");
        $exec = exec("php -S {$url} -t {$dir}");
        
        if(!$exec) {
            Cli::respondError("Unable to host app on: http://{$url}");
        }
    }

    public static function runSystemCommand($options)
    {
        $system = new System();
        $method_name = $options['n'] ?? null;
        $action = $options['o'] ?? null;

        if(!$method_name) {
            Cli::respondError('No system method specified', true);
        }

        if(!is_callable([$system, $method_name])) {
            return Cli::respondError('Invalid system command', true);
        }

        $raw_args = explode(':', $action);
        $args = array_map('trim', $raw_args);
        return call_user_func_array([$system, $method_name], $args);
    }

    public static function runSchema($command)
    {
        if(App::isProduction()) {
            return Cli::respondError('Cannot run command in production');
        }

        Cli::respond('...');

        $action = array_keys($command)[0] ?? 'r';
        $migration_name = isset($command['n']) ? strtolower($command['n']) . '.php' : null;

        $namespace = 'App\Migrations\\';
        $path = APP_MIGRATIONS_PATH;

        $actions = array(
            'r' =>  'up',
            'e' => 'empty',
            'd' => 'down'
        );

        $files = scandir($path);
        $dot_files = ['.', '..'];
        $count = 0;

        $trackable_files = array_diff($files, $dot_files);

        if(!count($trackable_files)) {
            return Cli::respondError('No migrations to run');
        }

        $action_name = $actions[$action];

        if(in_array($action, ['d', 'e']) && !$migration_name) {
            return Cli::respondError('Migration name not specified');
        }

        foreach($trackable_files as $filename) {

            $filepath = $path . DS . $filename;

            $usable_filename = strtolower($filename);

            if(is_dir($filepath) || !is_file($filepath)) {
                continue;
            }

            if($migration_name && $migration_name != $usable_filename) {
                continue;
            }

            $namevars = explode('.', $filename);
            $name = $namevars[0];
            $classname = $namespace . $name;

            if(!class_exists($classname)) {
                continue;
            }

            $count++;
            call_user_func([$classname, $action_name]);
            Cli::respondSuccess($action_name . ' -> Migration completed: ' . $name);
        }

        Cli::respond($count . ' migrations completed' . PHP_EOL);
    }

    private static function addExt($name, $capitalize = true)
    {
        $name_vars = explode('/', $name);
        $name_vars_capitalized = array_map('ucfirst', $name_vars);
        $name_vars_count = count($name_vars);

        $main_name = $name_vars[$name_vars_count - 1];
        $dirs = array_slice($name_vars_capitalized, 0, $name_vars_count - 1);

        $refined_name = $capitalize ? ucfirst($main_name) : strtolower($main_name);
        $new_dir = array_merge($dirs, [$refined_name]);
        $new_name = implode('/', $new_dir);

        return "{$new_name}.php";
    }

    private static function buildAsset($type, $name)
    {
        $allowed_types = ['css', 'js', 'vue', 'svelte', 'sass', 'scss', 'less'];
        $type = strtolower($type);

        if(!in_array($type, $allowed_types)) {
            return Cli::respondError("Unknown asset type {$type}", true);
        }

        $filename = "{$name}.{$type}";
        $template = self::getReservedTemplate('asset');
        $base_path = APP_STORAGE . DS . $type;

        if(!is_dir($base_path)) {
            mkdir($base_path, 0755);
        }

        $model_path = $base_path . DS . $filename;
        $refined_template = strtr($template, [
            '{name}' => $name,
            '{type}' => $type,
            '{date}' => date('jS-M-Y')
        ]);

        try {
            Cli::respond("creating {$type} asset: {$filename}");
            $file = new File($model_path, true, true);
            $file->write($refined_template);
            Cli::respondSuccess("created {$type} asset: {$filename}");
        }
        catch(FileSystemException $e) {
            Cli::respondError($e->getMessage());
            Cli::respondError("Unable to create {$type} asset: {$filename}", true);
        }

        return true;
    }

    private static function getClassName($name)
    {
        $name_vars = explode('/', $name);
        $vars_count = count($name_vars);
        $main_name = $name_vars[$vars_count - 1];

        return ucfirst($main_name);
    }

    private static function getClassNamespace($name)
    {
        $name_vars = explode('/', $name);
        $name_capitalized = array_map('ucfirst', $name_vars);
        $vars_count = count($name_vars);

        if($vars_count == 1) {
            return '';
        }

        echo $vars_count;

        $main_vars = array_slice($name_capitalized, 0, $vars_count - 1);
        $child_namespace = implode('\\', $main_vars);

        return '\\' . $child_namespace;
    }

    private static function getReservedTemplate($name)
    {
        $path = __DIR__ . DS . '.cli-reserved' . DS . "{$name}.tpl";
        
        try {
            $file = new File($path);
            $content = $file->getContent();
            return $content;
        }
        catch(FileSystemException $e) {
            Cli::respondError($e->getMessage());
            die();
        }
    }

    private static function getSyntaxedName($name, $syntax = '')
    {
        $name = ucfirst($name);

        if(!$syntax) {
            return $name;
        }

        $syntax_length = strlen($syntax);
        $raw_name = substr($name, 0, -$syntax_length);

        $suffix = substr($name, -$syntax_length, $syntax_length);
        $is_suffix = strtolower($suffix) === strtolower($syntax);

        return $is_suffix ? $raw_name . $syntax : $name . $syntax;
    }
}