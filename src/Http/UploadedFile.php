<?php

namespace Cube\Http;

use InvalidArgumentException;
use Cube\Misc\Storage;
use Cube\Interfaces\UploadedFileInterface;

class UploadedFile implements UploadedFileInterface
{

    /**
     * Specified file
     * 
     * @var string[] $file
     */
    private $file = array();

    /**
     * Specified file size
     * 
     * @var int
     */
    private $size = 0;

    /**
     * Specified file name
     * 
     * @var string
     */
    private $name = '';

    /**
     * Specified file type
     * 
     * @var string
     */
    private $type = '';

    /**
     * File temp location
     * 
     * @var string
     */
    private $temp_loc = '';

    /**
     * File error ID
     * 
     * @var int|null
     */
    private $error = null;

    /**
     * UploadedFile Constructor
     * 
     * @param array $file
     */
    public function __construct($file)
    {
        if(!is_array($file)) {
            throw new InvalidArgumentException('Argument "$file" should be an array');
        }

        $this->attach($file);
    }

    /**
     * Retrieve the file name sent by client
     * 
     * @return string
     */
    public function getClientFilename() {

        return $this->name;
    }

    /**
     * Retrive the media type sent by the client
     * 
     * @return string
     */
    public function getClientMediaType() {

        return $this->type;
    }

    /**
     * Retrieve extension from file name
     *
     * @return string
     */
    public function getExtensionFromName()
    {
        $vars = explode('.', $this->getClientFilename());
        return strtolower(array_pop($vars));
    }

    /**
     * Retrieve file error if any
     * 
     * @return int
     */
    public function getError() {

        return $this->error;
    }

    /**
     * Return value if file's extension is in fields
     *
     * @param array $fields
     * @return boolean
     */
    public function hasExtensionIn(array $fields)
    {
        $fields = array_map('strtolower', $fields);
        return in_array($this->getExtensionFromName(), $fields);
    }

    /**
     * Move file to target path
     * 
     * @param string $targetPath Path to save file to
     */
    public function moveTo($targetPath) {

        return Storage::save($this->getTempLink(), $targetPath);
    }

    /**
     * Retrieve file size
     * 
     * @return int|null
     */
    public function getSize() {

        return $this->size;
    }

    /**
     * Retrieve file temporary link
     * 
     * @return string
     */
    public function getTempLink()
    {
        return $this->temp_loc;
    }

    /**
     * Update file vars
     * 
     * @return self
     */
    private function attach($file)
    {
        $this->name = $file['name'] ?? null;
        $this->type = $file['type'] ?? null;
        $this->size = $file['size'] ?? null;
        $this->temp_loc = $file['tmp_name'] ?? null;
        $this->error = $file['error'];
    }
}