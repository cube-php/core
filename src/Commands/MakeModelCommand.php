<?php

namespace Cube\Commands;

use Cube\App\App;
use Cube\App\Directory;
use Cube\Exceptions\CliActionException;
use Cube\Helpers\Cli\CliActions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeModelCommand extends BaseCommand
{
    protected static $defaultName = 'make:model';

    public function configure()
    {
        $this->
            setDescription('Create a model')
            ->setHelp('This command helps create a model with boilerplate')
            ->addArgument('name', InputArgument::REQUIRED, 'Model name');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');

        try {

            CliActions::buildResource(
                $name,
                CliActions::RESOURCE_TYPE_MODEL
            );
        
        } catch(CliActionException $e) {
        
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