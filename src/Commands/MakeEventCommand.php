<?php

namespace Cube\Commands;

use Cube\Exceptions\CliActionException;
use Cube\Helpers\Cli\CliActions;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'make:event',
    description: 'Create an event',
    help: 'This command generates an event'
)]
class MakeEventCommand extends BaseCommand
{
    protected static $defaultName = 'make:event';

    public function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Event name');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');

        try {
            CliActions::buildResource(
                $name,
                CliActions::RESOURCE_TYPE_EVENT
            );
        } catch (CliActionException $e) {
            $output->writeln([
                '<fg=red>Unable to make event</>',
                concat('<fg=red>', $e->getMessage(), '</>')
            ]);

            return Command::FAILURE;
        }

        $output->writeln(
            concat('<info>', 'event ', $name, ' created successfully</info>')
        );

        return Command::SUCCESS;
    }
}
