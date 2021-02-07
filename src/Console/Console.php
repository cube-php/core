<?php

namespace Cube\Console;

use Cube\App\App;
use Cube\Interfaces\ConsoleInterface;

abstract class Console implements ConsoleInterface
{
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function onCall(array $args): ?string
    {
        return null;
    }
}