<?php

namespace Cube\Commands;

use Cube\Exceptions\CliActionException;
use Cube\Helpers\Cli\CliActions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MakeAppResourceCommand extends BaseCommand
{
    protected static $defaultName = 'make:app-resource';

    public function configure()
    {
        $this
            ->setDescription('Create a custom app resource')
            ->setHelp('This command helps you create custom app resource [eg. App\Services, App\Utils]')
            ->addArgument('name', InputArgument::REQUIRED, 'Resource name')
            ->addOption('dir', 'd', InputOption::VALUE_REQUIRED, 'Directory name');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $dir = $input->getOption('dir');

        try {

            CliActions::buildCustomResource(
                $name,
                (string) $dir
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
