<?php

namespace Cube\Interfaces;

interface RequestInterface
{
    public function getMethod();

    public function getAttribute($name);

    public function getBody();

    public function getParsedBody();

    public function getHeaders();

    public function getServer();

    public function inputs();

    public function input(string | array $name, string $defaults = '');

    public function setAttribute($name, $value);

    public function url();
}
