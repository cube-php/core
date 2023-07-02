<?php

namespace Cube\Helpers\Cli;

use Cube\App\App;
use Cube\App\Directory;
use Cube\Commands\AppSetupCommand;
use Cube\Commands\ConsoleCommand;
use Cube\Commands\CubeVersionCommand;
use Cube\Commands\EventDispatcherCommand;
use Cube\Commands\MakeAppResourceCommand;
use Cube\Commands\MakeControllerCommand;
use Cube\Commands\MakeEventCommand;
use Cube\Commands\MakeAssetCommand;
use Cube\Commands\MakeConsoleCommand;
use Cube\Commands\MakeExceptionCommand;
use Cube\Commands\MakeHelperCommand;
use Cube\Commands\MakeMiddlewareCommand;
use Cube\Commands\MakeMigrationCommand;
use Cube\Commands\MakeModelCommand;
use Cube\Commands\MakeRuleCommand;
use Cube\Commands\MigrateCommand;
use Cube\Commands\ServerCommand;
use Cube\Commands\SessionMigrateCommand;
use Cube\Commands\ViewClearCacheCommand;
use Cube\Exceptions\CubeCliException;
use Cube\Http\Env;
use Cube\Misc\File;
use Symfony\Component\Console\Application;
use Symfony\Component\Process\Process;

class Cli
{
    public const CONFIG_SUFFIX = 'suffix';

    private $app;

    protected $commands = array(
        MakeAppResourceCommand::class,
        EventDispatcherCommand::class,
        MakeControllerCommand::class,
        MakeMiddlewareCommand::class,
        ViewClearCacheCommand::class,
        SessionMigrateCommand::class,
        MakeMigrationCommand::class,
        MakeExceptionCommand::class,
        CubeVersionCommand::class,
        MakeConsoleCommand::class,
        MakeHelperCommand::class,
        MakeEventCommand::class,
        MakeAssetCommand::class,
        MakeModelCommand::class,
        AppSetupCommand::class,
        MakeRuleCommand::class,
        MigrateCommand::class,
        ConsoleCommand::class,
        ServerCommand::class
    );

    /**
     * Constructor
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        $app->setIsRunningViaTerminal(true);
        $app->init();
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

        $this->loadExtras($application);
        $application->run();
    }

    /**
     * Load extra commands
     *
     * @param Application $application
     * @return void
     */
    private function loadExtras(Application $application)
    {
        $dir = self::getExtrasDir();
        $content = json_decode(file_get_contents($dir));

        if (!$content) {
            return;
        }

        every($content, function ($class) use ($application) {

            if (!class_exists($class)) {
                return self::removeExtraPackageCommand($class);
            }

            $application->add(
                new $class($this->app)
            );
        });
    }

    /**
     * Add extra package command
     *
     * @param array $commands
     * @return void
     */
    public static function addExtraPackageCommand(array $commands)
    {
        $dir = self::getExtrasDir();
        $content = json_decode(file_get_contents($dir));

        if (!$content) {
            $content = [];
        }

        every($commands, function ($class) use ($content, $dir) {
            if (in_array($class, $content)) {
                return;
            }

            $content[] = $class;
            file_put_contents($dir, json_encode($content));
        });
    }

    /**
     * Remove command from extras
     *
     * @param string $class
     * @return void
     */
    public static function removeExtraPackageCommand(string $class)
    {
        $dir = self::getExtrasDir();
        $content = json_decode(file_get_contents($dir));

        if (!$content) {
            $content = [];
        }

        $values = array_values($content);

        if (!in_array($class, $values)) {
            return;
        }

        $index = array_search($class, $values);
        unset($content[$index]);

        file_put_contents($dir, json_encode($content));
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
        $php_path = Env::get('CLI_PHP_PATH');
        $bin_file = concat(App::getPath(Directory::PATH_ROOT), '/cube');
        $command = is_array($command) ? implode(' ', $command) : $command;

        if (!file_exists($bin_file)) {
            throw new CubeCliException('Cube executable file not found');
        }

        $output = [];
        $commands_list = [];

        if ($php_path) {
            $commands_list[] = $php_path;
        }

        $commands = array_merge($commands_list, [
            $bin_file,
            $command,
            $in_background ? '> /dev/null &' : ''
        ]);

        $executable_command = implode(' ', $commands);

        if ($in_background) {
            $output = exec($executable_command);
            return true;
        }

        $process = Process::fromShellCommandline($executable_command);
        $process->start();

        $process->wait(function ($type, $buffer) use (&$output) {
            $output[] = $buffer;
        });

        $content = implode(PHP_EOL, $output);

        if (!$process->isSuccessful()) {
            throw new CubeCliException($content);
        }

        return $content;
    }

    /**
     * Get extra installed packages dir
     *
     * @return string
     */
    private static function getExtrasDir()
    {
        $app = App::getRunningInstance();
        $cache_dir = $app->getPath(Directory::PATH_CACHE);
        $packages_dir = $cache_dir . '/packages';

        if (!is_dir($packages_dir)) {
            mkdir($packages_dir);
        }

        $file_path = $packages_dir . '/packages.json';

        if (!file_exists($file_path)) {
            new File($file_path, true);
        }

        return $file_path;
    }
}
