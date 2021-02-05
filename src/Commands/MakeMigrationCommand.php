<?php

namespace Cube\Commands;

use Cube\Exceptions\CliActionException;
use Cube\Helpers\Cli\CliActions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeMigrationCommand extends BaseCommand
{
    protected static $defaultName = 'make:migration';

    public function configure()
    {
        $this
            ->setDescription('Create a migration')
            ->setHelp('This command creates a new migration')
            ->addArgument('name', InputArgument::REQUIRED, 'Migration name');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');

        try {
            CliActions::buildResource(
                $name,
                CliActions::RESOURCE_TYPE_MIGRATION
            );
        } catch(CliActionException $e) {
            $output->writeln([
                '<fg=red>Unable to make migration</>',
                concat('<fg=red>', $e->getMessage() ,'</>')
            ]);

            return Command::FAILURE;
        }

        $output->writeln(
            concat('<info>', 'migration ', $name, ' created successfully</info>')
        );

        return Command::SUCCESS;
    }
}