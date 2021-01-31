<?php

namespace Cube\Interfaces;

interface UploadedFileInterface
{
    public function getClientFilename();

    public function getClientMediaType();

    public function getExtensionFromName();

    public function getError();

    public function getSize();

    public function hasExtensionIn(array $fields);

    public function moveTo($targetPath);
}