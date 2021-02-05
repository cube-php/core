<?php

namespace Cube\Commands;

use Cube\Exceptions\CliActionException;
use Cube\Helpers\Cli\CliActions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeMiddlewareCommand extends BaseCommand
{
    protected static $defaultName = 'make:middleware';

    public function configure()
    {
        $this
            ->setDescription('Create a middleware')
            ->setHelp('This command create a middleware')
            ->addArgument('name', InputArgument::REQUIRED, 'Middleware name');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');

        try {

            CliActions::buildResource(
                $name,
                CliActions::RESOURCE_TYPE_MIDDLEWARE
            );

        } catch(CliActionException $e) {

            $output->writeln([
                '<fg=red>Unable to make middleware</>',
                concat('<fg=red>', $e->getMessage() ,'</>')
            ]);

            return Command::FAILURE;
        }

        $output->writeln(
            concat('<info> middleware ', $name ,' created successfully</info>')
        );
        return Command::SUCCESS;
    }
}