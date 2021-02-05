<?php

namespace Cube\Commands;

use Cube\Exceptions\CliActionException;
use Cube\Helpers\Cli\CliActions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeControllerCommand extends BaseCommand
{
    protected static $defaultName = 'make:controller';

    public function configure()
    {
        $this
            ->setDescription('Create a controller')
            ->setHelp('This command helps you create a controller')
            ->addArgument('name', InputArgument::REQUIRED, 'Controller name');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');

        try {

            CliActions::buildResource(
                $name,
                CliActions::RESOURCE_TYPE_CONTROLLER
            );
        } catch (CliActionException $e) {

            $output->writeln([
                '<fg=red>Unable to create controller</>',
                concat('<fg=red>', $e->getMessage(), '</>')
            ]);

            return Command::FAILURE;
        }

        $output->writeln(
            concat('<info>controller', $name ,' created successfully</info>')
        );
        
        return Command::SUCCESS;
    }
}