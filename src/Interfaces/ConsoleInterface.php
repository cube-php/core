<?php

namespace Cube\Interfaces;

use Cube\App\App;

interface ConsoleInterface
{
    public function __construct(App $app);

    public function onCall(array $args): ?string;
}