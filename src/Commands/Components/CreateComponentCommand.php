<?php

namespace Cube\Commands\Components;

use Cube\Commands\BaseCommand;
use Cube\Exceptions\CliActionException;
use Cube\Helpers\Cli\CliActions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateComponentCommand extends BaseCommand
{
    protected static $defaultName = 'make:component';

    public function configure()
    {
        $this
            ->setDescription('Create a view component')
            ->setHelp('This command helps you create a view component')
            ->addArgument('name', InputArgument::REQUIRED, 'Resource name');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');

        try {

            CliActions::buildResource(
                $name,
                CliActions::RESOURCE_TYPE_COMPONENT
            );
        } catch (CliActionException $e) {

            $output->writeln([
                concat('<fg=red>Unable to create custom resource: "', $e->getMessage(), '"</>')
            ]);

            return Command::FAILURE;
        }

        $output->writeln(
            concat('<info>controller ', $name, ' created successfully</info>')
        );

        return Command::SUCCESS;
    }
}
