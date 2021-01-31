<?php

namespace Cube\Misc;

use Cube\Http\UploadedFile;

class FilesParser
{

    /**
     * Check if is multi upload
     * 
     * @var bool
     */
    private $is_multi = true;

    /**
     * Raw filelist
     * 
     * @var array[]
     * 
     */
    private $files;

    /**
     * Parsed filelist
     * 
     * @var array[]
     */
    private $filelist = array();

    /**
     * FileParser constructor
     * 
     * @param array[] $files $_FILES
     * 
     */
    public function __construct($files)
    {

        $this->files = $files;

        $key = array_keys($files)[0] ?? null;
        if($key) {
            
            $multiChecker = $files[$key]['name'] ?? null;
            $this->is_multi = !($multiChecker && !is_array($multiChecker));
        }
    }

    /**
     * Re-array file list
     * 
     * @return array[]
     */
    public function build()
    {

        $walker = function($arr, $fileInfoKey, callable $walker)
        {
            $ret = array();

            foreach($arr as $k => $v)
            {
                if(is_array($v)) {

                    $ret[$k] = $walker($v, $fileInfoKey, $walker);

                } else {

                    $ret[$k][$fileInfoKey] = $v;

                }
            }

            return $ret;
        };

        $files = array();

        foreach($this->files as $name => $values)
        {
            if(!isset($files[$name])) $files[$name] = array();

            if(!is_array($values['error'])) {

                $files[$name] = $values;

            } else {

                foreach($values as $fileInfoKey => $subArray)
                {
                    $files[$name] = array_replace_recursive(
                        $files[$name],
                        $walker($subArray, $fileInfoKey, $walker)
                    );
                }
            }
        }

        return $files;
    }

    /**
     * Parse assigned files
     * 
     * @return array
     */
    public function reparse($data)
    {
        if(!is_array($data)) return $data;

        if(!$this->isAssociativeArray($data)) return $data;

        foreach($data as $key => $val)
        {
            if($this->isFileArray($val)) {
                return [$val];
            }

            $data = $this->reparse($val);
        }

        return $data;
    }

    /**
     * Parse file via index
     * 
     * @return array
     */
    public function parseIndex($array)
    {
        $root = [];
        
        foreach($array as $key => $value)
        {

            if(!isset($root[$key])) $root[$key] = array();

            if(
                !(
                    is_array($value) &&
                    $this->isAssociativeArray($value) &&
                    !$this->isFileArray($value)
                )
            ) {
                $root[$key] = $this->parse2index($value);
                return $root;
            }

            $root[$key] = $this->parseIndex($value);
        }

        return $root;
    }

    /**
     * Add the UploadedFile Class to files
     * 
     * @return Cube\Http\UploadedFile[]
     */
    public function parse2index()
    {
        $root = $filelist = [];
        $data = $this->build();
        $mainlist = $this->reparse($data);
        
        foreach($mainlist as $file)
        {
            $filelist[] = new UploadedFile($file);
        }

        return $filelist;
    }

    /**
     * Parse uplodaded files
     * 
     * @return array
     */
    public function parse()
    {

        if(!$this->is_multi && count($this->files)) {

            $key = array_keys($this->files)[0];
            return array($key => new UploadedFile($this->files[$key]));
        }

        return $this->parseIndex($this->build());
    }

    /**
     * Check if $data is an associative array
     * 
     * @return bool
     */
    private function isAssociativeArray($data)
    {
        if(!is_array($data)) return false;
        return (array_keys($data) !== range(0, count($data) - 1));
    }

    /**
     * Check if $arr is a file resource
     * 
     * @return bool
     */
    private function isFileArray($val)
    {
        return (
            isset($val['name']) &&
            isset($val['tmp_name']) &&
            isset($val['error']) &&
            isset($val['size']) &&
            isset($val['type'])
        );
    }
}