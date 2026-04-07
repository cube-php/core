<?php

namespace Cube\Commands\Router;

use Cube\Commands\BaseCommand;
use Cube\Router\ControllerRoutesLoader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'router:clear-cache',
    description: 'Clear router cache'
)]
class RouterClearCacheCommand extends BaseCommand
{
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        ControllerRoutesLoader::clearCachedRoutes();

        $output->writeln(
            '<info>[✓] Routes cleared successfully</info>'
        );

        return Command::SUCCESS;
    }
}
