<?php

namespace Cube\Commands;

use Cube\Exceptions\CliActionException;
use Cube\Helpers\Cli\CliActions;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'make:app-resource',
    description: 'Create a custom app resource',
    help: 'This command helps you create custom app resource [eg. App\Services, App\Utils]',
)]
class MakeAppResourceCommand extends BaseCommand
{
    public function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Resource name')
            ->addOption('dir', 'd', InputOption::VALUE_REQUIRED, 'Directory name');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
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
