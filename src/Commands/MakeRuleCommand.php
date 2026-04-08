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
    name: 'make:rule',
    description: 'Create a new validation rule',
    help: 'This command helps to generate a new validation rule'
)]
class MakeRuleCommand extends BaseCommand
{
    public function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Rule name');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');

        try {
            CliActions::buildResource(
                $name,
                CliActions::RESOURCE_TYPE_VALIDATION_RULE
            );
        } catch (CliActionException $e) {
            $output->writeln([
                '<fg=red>Unable to create validation rule</>',
                concat('<fg=red>', $e->getMessage(), '</>')
            ]);

            return Command::FAILURE;
        }

        $output->writeln(
            concat(
                '<info>',
                'rule ',
                $name,
                ' created successfully</info>'
            )
        );

        return Command::SUCCESS;
    }
}
