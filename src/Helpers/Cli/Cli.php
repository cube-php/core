<?php

namespace Cube\Helpers\Cli;

use Cube\App\App;
use Cube\App\Directory;
use Cube\Commands\ConsoleCommand;
use Cube\Commands\EventDispatcherCommand;
use Cube\Commands\MakeControllerCommand;
use Cube\Commands\MakeEventCommand;
use Cube\Commands\MakeAssetCommand;
use Cube\Commands\MakeConsoleCommand;
use Cube\Commands\MakeExceptionCommand;
use Cube\Commands\MakeHelperCommand;
use Cube\Commands\MakeMiddlewareCommand;
use Cube\Commands\MakeMigrationCommand;
use Cube\Commands\MakeModelCommand;
use Cube\Commands\MigrateCommand;
use Cube\Commands\ServerCommand;
use Cube\Exceptions\CubeCliException;
use Symfony\Component\Console\Application;
use Symfony\Component\Process\Process;

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
        MigrateCommand::class,
        EventDispatcherCommand::class,
        ConsoleCommand::class,
        MakeConsoleCommand::class,
        MakeHelperCommand::class
    );

    /**
     * Constructor
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        $app->isRunningViaTerminal(true);
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

    /**
     * Run cube cli command
     *
     * @param string $command
     * @param boolean $in_background
     * @param boolean $should_wait
     * @throws CubeCliException
     * 
     * @return string|bool
     */
    public static function run($command, bool $in_background = false)
    {
        $bin_file = concat(App::getPath(Directory::PATH_ROOT), '/cube');
        $command = is_array($command) ? implode(' ', $command) : $command;

        if(!file_exists($bin_file)) {
            throw new CubeCliException('Cube executable file not found');
        }

        $output = [];
        $commands = array(
            $bin_file,
            $command,
            $in_background ? '> /dev/null &' : ''
        );

        $executable_command = implode(' ', $commands);

        if($in_background) {
            $output = exec($executable_command);
            return true;
        }

        $process = Process::fromShellCommandline($executable_command);
        $process->start();

        $process->wait(function ($type, $buffer) use (&$output) {
            $output[] = $buffer;
        });

        $content = implode(PHP_EOL, $output);

        if(!$process->isSuccessful()) {
            throw new CubeCliException($content);
        }

        return $content;
    }
}