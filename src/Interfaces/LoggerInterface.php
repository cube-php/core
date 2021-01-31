<?php

namespace Cube\Interfaces;

interface LoggerInterface
{
    public function get();

    public function set(string $content);
}