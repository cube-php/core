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
        $key = isset(array_keys($files)[0]) ? array_keys($files)[0] : null;

        if ($key) {
            $multiChecker = $files[$key]['name'] ?? null;
            $this->is_multi = ($multiChecker && is_array($multiChecker));
        }
    }

    /**
     * Re-array file list
     * 
     * @return array[]
     */
    public function build()
    {
        $files = array();

        every($this->files, function ($value, $key) use (&$files) {

            if (!isset($files[$key])) {
                $files[$key] = array();
            }

            every($value, function ($fileValue, $fileKey) use (&$files, $key) {

                every($fileValue, function ($value, $index) use (&$files, $fileKey, $key) {

                    if (!isset($files[$key][$index])) {
                        $files[$key][$index] = array();
                    }

                    $files[$key][$index][$fileKey] = $value;
                });
            });
        });

        return $files;
    }

    /**
     * Parse assigned files
     * 
     * @return array
     */
    public function reparse($data)
    {
        if (!is_array($data)) return $data;

        if (!$this->isAssociativeArray($data)) return $data;

        foreach ($data as $key => $val) {
            if ($this->isFileArray($val)) {
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

        foreach ($array as $key => $value) {

            if (!isset($root[$key])) $root[$key] = array();

            if (
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
        $filelist = [];
        $data = $this->build();
        $mainlist = $this->reparse($data);

        foreach ($mainlist as $file) {
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
        if (!$this->is_multi && count($this->files)) {
            $keys = array_keys($this->files);
            $files = array();
            array_walk($keys, function ($key) use (&$files) {
                $files[$key] = new UploadedFile($this->files[$key]);
            });

            return $files;
        }

        return $this->parseIndex(
            $this->build()
        );
    }

    /**
     * Check if $data is an associative array
     * 
     * @return bool
     */
    private function isAssociativeArray($data)
    {
        if (!is_array($data)) return false;
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
