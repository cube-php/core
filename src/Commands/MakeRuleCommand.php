<?php

namespace Cube\Commands;

use Cube\Exceptions\CliActionException;
use Cube\Helpers\Cli\CliActions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeRuleCommand extends BaseCommand
{
    protected static $defaultName = 'make:rule';

    public function configure()
    {
        $this
            ->setDescription('Create a new validation rule')
            ->setHelp('This command helps to generate a new validation rule')
            ->addArgument('name', InputArgument::REQUIRED, 'Rule name');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');

        try {
            CliActions::buildResource(
                $name,
                CliActions::RESOURCE_TYPE_VALIDATION_RULE
            );
        } catch(CliActionException $e) {
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
                ' created succesfully</info>'
            )
        );

        return Command::SUCCESS;
    }
}