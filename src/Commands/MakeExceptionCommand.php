<?php

namespace Cube\Commands;

use Cube\Exceptions\CliActionException;
use Cube\Helpers\Cli\CliActions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeExceptionCommand extends BaseCommand
{
    protected static $defaultName = 'make:exception';

    public function configure()
    {
        $this
            ->setDescription('Create an exception')
            ->setHelp('This command generates an exception')
            ->addArgument('name', InputArgument::REQUIRED, 'Event name');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');

        try {
            CliActions::buildResource(
                $name,
                CliActions::RESOURCE_TYPE_EXCEPTION
            );
        } catch(CliActionException $e) {
            $output->writeln([
                '<fg=red>Unable to make exception</>',
                concat('<fg=red>', $e->getMessage() ,'</>')
            ]);

            return Command::FAILURE;
        }

        $output->writeln(
            concat('<info>', 'exception ', $name, ' created successfully</info>')
        );

        return Command::SUCCESS;
    }
}