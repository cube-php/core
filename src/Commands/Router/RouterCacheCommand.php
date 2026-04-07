<?php

namespace Cube\Commands\Router;

use Cube\Commands\BaseCommand;
use Cube\Router\ControllerRoutesLoader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'router:cache',
    description: 'Generate a new cache for controller declared routes',
    help: 'This command generates a new cache for controller declared routes'
)]
class RouterCacheCommand extends BaseCommand
{
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        ControllerRoutesLoader::cacheRoutes();

        $output->writeln(
            '<info>[✓] Routes cached successfully</info>'
        );

        return Command::SUCCESS;
    }
}
