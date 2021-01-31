<?php

namespace Cube\Interfaces;

interface UriInterface
{
    
    public function __toString();

    public function getHost();

    public function getQuery($name);

    public function getQueryParams();

    public function getPath();

    public function getPort();
    
    public function getScheme();
}