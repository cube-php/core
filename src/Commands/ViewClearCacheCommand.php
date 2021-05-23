<?php

namespace Cube\Commands;

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
        $app = $this->getApplication();
        $view_config = $this->app->getConfig('view');
        $dir = $view_config['cache_dir'] ?? null;

        if(!$dir) {
            $output->writeln(
                concat('<fg=red>', 'Cache directory not set' ,'</>')
            );
            return self::FAILURE;
        }

        if(!is_dir($dir)) {
            $output->writeln(
                concat('<fg=yellow>', 'No view cache to clear' ,'</>')
            );
            return self::FAILURE;
        }

        unlink_dir_files($dir);
        $output->writeln(
            concat('<fg=green>', 'View cache cleared!' ,'</>')
        );

        return self::SUCCESS;
    }
}