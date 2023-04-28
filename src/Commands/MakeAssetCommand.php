<?php

namespace Cube\Commands;

use Cube\Exceptions\CliActionException;
use Cube\Helpers\Cli\CliActions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MakeAssetCommand extends BaseCommand
{
    protected static $defaultName = 'make:asset';

    public function configure()
    {
        $this
            ->setDescription('Generate asset')
            ->setHelp('This command generates css,js,etc assets in the right directories')
            ->addArgument('name', InputArgument::REQUIRED, 'Asset name')
            ->addOption('type', 't', InputOption::VALUE_REQUIRED, 'Asset type');
    }

    public function execute(InputInterface $input, OutputInterface $output)
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
