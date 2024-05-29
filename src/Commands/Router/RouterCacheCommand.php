<?php

namespace Cube\Commands\Router;

use Cube\Commands\BaseCommand;
use Cube\Router\ControllerRoutesLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RouterCacheCommand extends BaseCommand
{
    protected static $defaultName = 'router:cache';

    public function configure()
    {
        $this->setDescription(
            'Generate a new cache for controller declared routes'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        ControllerRoutesLoader::cacheRoutes();

        $output->writeln(
            '<info>[✓] Routes cached successfully</info>'
        );

        return Command::SUCCESS;
    }
}
