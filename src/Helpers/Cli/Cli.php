<?php

namespace Cube\Helpers\Cli;

use Cube\App\App;
use Cube\Commands\MakeControllerCommand;
use Cube\Commands\MakeEventCommand;
use Cube\Commands\MakeAssetCommand;
use Cube\Commands\MakeExceptionCommand;
use Cube\Commands\MakeMiddlewareCommand;
use Cube\Commands\MakeMigrationCommand;
use Cube\Commands\MakeModelCommand;
use Cube\Commands\MigrateCommand;
use Cube\Commands\ServerCommand;
use Symfony\Component\Console\Application;

class Cli
{
    private $app;

    protected $commands = array(
        ServerCommand::class,
        MakeModelCommand::class,
        MakeAssetCommand::class,
        MakeEventCommand::class,
        MakeMiddlewareCommand::class,
        MakeMigrationCommand::class,
        MakeControllerCommand::class,
        MakeExceptionCommand::class,
        MigrateCommand::class
    );

    /**
     * Constructor
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * Listen for command
     *
     * @return void
     */
    public function listen()
    {
        $application = new Application();

        array_walk($this->commands, function ($class) use ($application) {
            $application->add(new $class($this->app));
        });

        $application->run();
    }
}