<?php

namespace Cube\Commands;

use Cube\Exceptions\CliActionException;
use Cube\Helpers\Cli\CliActions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeConsoleCommand extends BaseCommand
{
    protected static $defaultName = 'make:console-command';

    public function configure()
    {
        $this->
            setDescription('Create a console command')
            ->setHelp('This command helps create a console commands with boilerplate')
            ->addArgument('name', InputArgument::REQUIRED, 'Console command name');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');

        try {

            CliActions::buildResource(
                $name,
                CliActions::RESOURCE_TYPE_COMMANDS
            );
        
        } catch(CliActionException $e) {
        
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