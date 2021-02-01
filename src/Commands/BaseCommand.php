<?php

namespace Cube\Commands;

use Cube\App\App;
use Symfony\Component\Console\Command\Command;

abstract class BaseCommand extends Command
{
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
        parent::__construct();
    }
}