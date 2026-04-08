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
    name: 'make:asset',
    description: 'Generate asset',
    help: 'This command generates css,js,etc assets in the right directories'
)]
class MakeAssetCommand extends BaseCommand
{
    public function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Asset name')
            ->addOption('type', 't', InputOption::VALUE_REQUIRED, 'Asset type');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $type = $input->getOption('type');

        try {

            CliActions::buildAsset(
                $type,
                $name
            );
        } catch (CliActionException $e) {
            $output->writeln([
                '<fg=red>Unable to generate asset</>',
                concat('<fg=red>', $e->getMessage(), '</>')
            ]);

            return Command::FAILURE;
        }

        $output->writeln(
            concat('<info>', $name, ' ', $type, ' asset generated successfully', '</info>')
        );

        return Command::SUCCESS;
    }
}
