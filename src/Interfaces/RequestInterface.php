<?php

namespace Cube\Interfaces;

use Cube\Http\Session\SessionHandler;
use Cube\Misc\Collection;

interface RequestInterface
{
    public function getMethod();

    public function getAttribute($name);

    public function getBody();

    public function getParsedBody();

    public function getHeaders();

    public function getServer();

    public function getCookies(): Collection;

    public function inputs();

    public function input(string | array $name, string $defaults = '');

    public function session(): SessionHandler;

    public function setAttribute($name, $value);

    public function url();

    public function setCUstomMethod(string $name, callable $callback);
}
