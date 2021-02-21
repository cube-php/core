<?php

namespace Cube\Helpers\Cli;

use Cube\App\App;
use Cube\App\Directory;
use Cube\Exceptions\CliActionException;
use Cube\Misc\File;
use Cube\Exceptions\FileSystemException;

class CliActions
{

    public const RESOURCE_TYPE_MODEL = 'Model';
    public const RESOURCE_TYPE_CONTROLLER = 'Controller';
    public const RESOURCE_TYPE_MIGRATION = 'Migration';
    public const RESOURCE_TYPE_EXCEPTION = 'Exception';
    public const RESOURCE_TYPE_MIDDLEWARE = 'Middleware';
    public const RESOURCE_TYPE_EVENT = 'Event';
    public const RESOURCE_TYPE_COMMANDS = 'Command';
    public const RESOURCE_TYPE_HELPER = 'helper';
    
    /**
     * Build & generate resouce
     *
     * @param string $raw_name
     * @param string $type
     * @param bool $add_type Add type to name
     * @return bool
     */
    public static function buildResource(string $raw_name, string $type, bool $add_type = true)
    {
        $space = concat($type, 's');
        $name = self::getSyntaxedName($raw_name, $add_type ? $type : '');

        $filename = self::addExt($name, $add_type);
        $template = self::getReservedTemplate(strtolower($type));

        $fpath = self::generateModulePath($space, $filename);
        $refined_template = strtr($template, [
            '{fn}' => $name,
            '{name}' => $raw_name,
            '{className}' => self::getClassName($name),
            '{subNamespace}' => self::getClassNamespace($name)
        ]);

        try {
            $file = new File($fpath, true, true);
            $file->write($refined_template);
        }
        catch(FileSystemException $e) {
            throw new CliActionException($e->getMessage());
        }

        return true;
    }

    /**
     * Build asset
     *
     * @param string $type
     * @param string $name
     * @return bool
     */
    public static function buildAsset($type, $name)
    {
        $allowed_types = ['css', 'js', 'vue', 'svelte', 'sass', 'scss', 'less'];
        $type = strtolower($type);

        if(!in_array($type, $allowed_types)) {
            throw new CliActionException('Invalid asset type');
        }

        $filename = "{$name}.{$type}";
        $template = self::getReservedTemplate('asset');
        $base_path = concat(App::getPath(Directory::PATH_STORAGE), DIRECTORY_SEPARATOR, $type);

        if(!is_dir($base_path)) {
            mkdir($base_path, 0755);
        }

        $model_path = $base_path . DIRECTORY_SEPARATOR . $filename;
        $refined_template = strtr($template, [
            '{name}' => $name,
            '{type}' => $type,
            '{date}' => date('jS-M-Y')
        ]);

        try {
            $file = new File($model_path, true, true);
            $file->write($refined_template);
        }
        catch(FileSystemException $e) {
            throw new CliActionException($e->getMessage());
        }

        return true;
    }

    /**
     * Add file extension
     *
     * @param string $name
     * @param boolean $capitalize
     * @return string
     */
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

        return concat($new_name, '.php');
    }

    /**
     * Determin class name
     *
     * @param string $name
     * @return string
     */
    private static function getClassName($name)
    {
        $name_vars = explode('/', $name);
        $vars_count = count($name_vars);
        $main_name = $name_vars[$vars_count - 1];

        return ucfirst($main_name);
    }

    /**
     * Determine class namespace
     *
     * @param string $name
     * @return string
     */
    private static function getClassNamespace($name)
    {
        $name_vars = explode('/', $name);
        $name_capitalized = array_map('ucfirst', $name_vars);
        $vars_count = count($name_vars);

        if($vars_count == 1) {
            return '';
        }

        $main_vars = array_slice($name_capitalized, 0, $vars_count - 1);
        $child_namespace = implode('\\', $main_vars);

        return '\\' . $child_namespace;
    }

    /**
     * Generate path to module
     *
     * @param string $namespace
     * @param string $filename
     * @return string
     */
    public static function generateModulePath(string $namespace, string $filename)
    {
        $path = concat(App::getPath(Directory::PATH_APP), '/', $namespace);
        return concat($path, DIRECTORY_SEPARATOR, $filename);
    }

    /**
     * Get reserved template for resource
     *
     * @param string $name
     * @return string
     */
    private static function getReservedTemplate($name)
    {
        $path = __DIR__ . DIRECTORY_SEPARATOR . '.cli-reserved' . DIRECTORY_SEPARATOR . "{$name}.tpl";
        
        try {
            $file = new File($path);
            $content = $file->getContent();
            return $content;
        }
        catch(FileSystemException $e) {
            throw new CliActionException($e->getMessage());
        }
    }

    /**
     * Generate class name
     *
     * @param string $name
     * @param string $syntax
     * @return string
     */
    public static function getSyntaxedName($name, $syntax = '')
    {
        $name_vars = preg_split('/\-|\_/', $name);
        $formatted_name_vars = array_map('ucfirst', $name_vars);
        $new_name = implode('', $formatted_name_vars);

        if(!$syntax) {
            return strtolower($new_name);
        }

        $syntax_length = strlen($syntax);
        $raw_name = substr($new_name, 0, -$syntax_length);

        $suffix = substr($new_name, -$syntax_length, $syntax_length);
        $is_suffix = strtolower($suffix) === strtolower($syntax);

        return $is_suffix ? $raw_name . $syntax : $new_name . $syntax;
    }
}