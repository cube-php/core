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
    name: 'make:model',
    description: 'Create a model',
    help: 'This command helps create a model with boilerplate'
)]
class MakeModelCommand extends BaseCommand
{
    public function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Model name');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');

        try {

            CliActions::buildResource(
                $name,
                CliActions::RESOURCE_TYPE_MODEL
            );
        } catch (CliActionException $e) {

            $output->writeln([
                '<fg=red>Unable to generate model</>',
                concat('<fg=red>', $e->getMessage(), '</>')
            ]);
            return Command::FAILURE;
        }

        $output->writeln(
            concat('<info>', $name, ' model generated successfully </info>')
        );
        return Command::SUCCESS;
    }
}
