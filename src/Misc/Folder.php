<?php

namespace Cube\Misc;

use Cube\Exceptions\FileSystemException;

class Folder
{
    
    /**
     * Create new folder
     * 
     * @param string $dir Directory to create new folder into
     * @param string $name Name for the new folder
     * @param int|string $chmod Chmod for folder
     * @param boolean $silent Do/Don't throw folder exists exception
     * 
     * @return string $path
     */
    public static function create($dir, $name, $chmod = 0775, $silent = false){

        $folder_path = $dir . DS . $name;
        $exists = static::exists($folder_path);

        if($exists && !$silent) {
            throw new FileSystemException
                ('Folder "'. $name .'" already exists in "' . $dir . '"');
        }

        if($exists && $silent) {
            return true;
        }

        if(!mkdir($folder_path, $chmod, true)) {
            throw new FileSystemException('Unable to create folder');
        }

        static::chmod($folder_path, $chmod);
        return $folder_path;
    }

    /**
     * Change folder mode
     * 
     * @param string    $filename File path
     * @param int       $mode File mode
     * 
     * @return bool
     */
    public static function chmod($filename, $mode)
    {
        if(!static::exists($filename)) {
            throw new FileSystemException('"' . $filename . '" does not exist');
        }

        return chmod($filename, $mode);
    }

    /**
     * Delete specifiled folder
     * 
     * @param string $path Folder path
     */
    public static function delete($path)
    {
        if(!unlink($path)) {
            throw new FileSystemException('Unable to delete folder "' . $path . '"');
        }

        return true;
    }

    /**
     * Check the existence of folder by path
     * 
     * @param string $path Folder path
     * 
     * @return bool
     */
    public static function exists($path)
    {
        return is_dir($path);
    }
}