<?php

namespace Cube\Commands\Router;

use Cube\Commands\BaseCommand;
use Cube\Router\ControllerRoutesLoader;
use Cube\Router\RouteCollection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RouterClearCacheCommand extends BaseCommand
{
    protected static $defaultName = 'router:clear-cache';

    public function configure()
    {
        $this->setDescription(
            'Clear router cache'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        ControllerRoutesLoader::clearCachedRoutes();

        $output->writeln(
            '<info>[✓] Routes cleared successfully</info>'
        );

        return Command::SUCCESS;
    }
}
