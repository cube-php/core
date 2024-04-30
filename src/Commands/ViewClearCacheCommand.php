<?php

namespace Cube\Commands;

use Cube\App\App;
use Cube\App\Directory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ViewClearCacheCommand extends BaseCommand
{
    protected static $defaultName = 'view:clear-cache';

    public function configure()
    {
        $this
            ->setDescription('Clear views cache')
            ->setHelp('This command clears the cached views');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $view_config = $this->app->getConfig('view');
        $dir = $view_config['cache_dir'] ?? null;

        if (!$dir) {
            $output->writeln(
                concat('<fg=red>', 'Cache directory not set', '</>')
            );
            return self::FAILURE;
        }

        $cache_dir = App::getRunningInstance()->getPath(
            Directory::PATH_CACHE
        );

        $main_dir = $cache_dir . DIRECTORY_SEPARATOR . $dir;

        if (!is_dir($main_dir)) {
            $output->writeln(
                concat('<fg=yellow>', 'No view cache to clear', '</>')
            );
            return self::FAILURE;
        }

        unlink_dir_files($main_dir);
        $output->writeln(
            concat('<fg=green>', 'View cache cleared!', '</>')
        );

        return self::SUCCESS;
    }
}
