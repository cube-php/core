<?php

namespace Cube\Interfaces;

interface CollectionInterface
{

    public function all();

    public function clear();

    public function has($name);

    public function remove($name);

    public function set($name, $value);
}