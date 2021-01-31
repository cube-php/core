<?php

namespace Cube\Misc;

use Cube\Exceptions\FileSystemException;

class File
{

    /**
     * File resource
     * 
     * @var file
     */
    private $_file;

    /**
     * File path
     * 
     * @var string
     */
    private $_path;
    
    /**
     * File constructor
     * 
     * @param string    $path File path
     * @param bool      $create_new  Whether to create new file if not exists
     * @param bool      $throw_exists_exception Whether to throw an exception if file already exists
     */
    public function __construct($path, $create_new = false, $throw_exists_exception = false)
    {   
        $this->_path = $path;
        $exists = file_exists($this->_path);
        
        if($exists && $throw_exists_exception) {
            throw new FileSystemException
                ('File at "' . $this->_path . ' already exists"');
        }
        
        if(!$exists && $create_new) {
            $dir_vars = explode('/', $path);
            $dir_name_vars = array_slice($dir_vars, 0, count($dir_vars) - 1);
            $dir_name = implode(DS, $dir_name_vars);
            Folder::create($dir_name, '', 0775, true);
        }

        $modes = !$exists && $create_new ? 'w+' : 'a+';
        $file = @fopen($path, $modes);

        if(!$file) {
            throw new FileSystemException
                ('"' . $path . '" Failed to open stream, File not found!');
        }

        $this->_file = $file;
    }

    /**
     * Change file mode
     * 
     * @param int $mode New file mode
     * 
     * @return self
     */
    public function chmod($mode)
    {
        chmod($this->_path, $mode);
        return $this;
    }

    /**
     * Delete file
     * 
     * @return
     */
    public function delete()
    {
        @unlink($this->_path);
        return true;
    }

    /**
     * Return file contents
     * 
     * @return string|resource
     */
    public function getContent()
    {
        return file_get_contents($this->_path);
    }

    /**
     * Returns file resource
     * 
     * @return resource
     */
    public function getFile()
    {
        return $this->_file;
    }

    /**
     * Read file
     *
     * @return int
     */
    public function read()
    {
        return readfile($this->_path);
    }

    /**
     * Rename file
     * 
     * @return string New link
     */
    public function rename($new_name)
    {
        #Close the file process to allow renaming
        $this->close();
        $filename = basename($this->_path);

        $dir = str_replace($filename, '', $this->_path);
        $new_path = "{$dir}{$new_name}";
        $rename = @rename($this->_path, $new_path);
        
        if(!$rename) {
            throw new FileSystemException
                ('Unable to rename file "' . $filename . '" to "' . $new_name . '"');
        }

        return new self($new_path);
    }

    /**
     * Write content to file
     * 
     * @param string|blob $content Content to write to file
     * @return self
     */
    public function write($content)
    {
        fwrite($this->_file, $content);
        return $this;
    }

    /**
     * Make path from arguments
     *
     * @param array ...$args
     * @return string
     */
    public static function joinPath(...$args)
    {
        return implode(DS, $args);
    }

    /**
     * Close opened file resource
     * 
     * @return
     */
    private function close()
    {
        fclose($this->_file);
    }
}