<?php

namespace Cube\Misc;

use Cube\Misc\Folder;

use Cube\Exceptions\FileSystemException;

class Storage
{

    /**
     * Create folder in storage path
     * 
     * @param string $folder_path Folder path
     * @param int $folder_mode Folder chmod
     * 
     * @return string   Folder link
     */
    public static function createFolder($folder_path, $folder_mode = null)
    {
        $create = Folder::create(APP_STORAGE, $folder_path, $folder_mode, true);
        return static::getPath(null, $folder_path);
    }

    /**
     * Get storage path
     * 
     * @param string $folder Folder in app storage directory
     * @param string $filename
     * 
     * @return string $path Storage path
     */
    public static function getPath($folder, $filename = '')
    {
        $folder = $folder ?? '';
        $folder_path = APP_STORAGE . $folder;

        if(!Folder::exists($folder_path)) {
            throw new FileSystemException('Path "' . $folder_path . '" not found in storage');
        }

        $path = $filename ? $filename : '';
        return $folder_path . $path;
    }

    /**
     * Save file to storage
     * 
     * @param string $path Temporary file path
     * @param string $target_path Target path
     * 
     * @return bool
     */
    public static function save($path, $target_path)
    {
        #Check if path is url to use the appropriate method
        $is_url = filter_var($path, FILTER_VALIDATE_URL);

        #get the right method and action name
        $method_name = $is_url ? 'copy' : 'move_uploaded_file';
        $action_name = $is_url ? 'copy' : 'move';

        #Set real target path
        $real_target_path = APP_STORAGE . $target_path;

        #Move file, We don't want PHP to throw errors on error
        $save = @$method_name($path, $real_target_path);

        if(!$save)
        {
            throw new FileSystemException
                ('Unable to ' . $action_name . ' "'. $path  .'" to "' . $real_target_path . '"');
        }

        return true;
    }
}