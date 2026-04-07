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
    name: 'make:console-command',
    description: 'Create a console command',
    help: 'This command helps create a console commands with boilerplate'
)]
class MakeConsoleCommand extends BaseCommand
{
    public function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Console command name');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');

        try {

            CliActions::buildResource(
                $name,
                CliActions::RESOURCE_TYPE_COMMANDS
            );
        } catch (CliActionException $e) {

            $output->writeln([
                '<fg=red>Unable to generate console command</>',
                concat('<fg=red>', $e->getMessage(), '</>')
            ]);
            return Command::FAILURE;
        }

        $output->writeln(
            concat('<info>', $name, ' console command generated successfully </info>')
        );
        return Command::SUCCESS;
    }
}
