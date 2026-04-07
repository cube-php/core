<?php

namespace Cube\Commands;

use Cube\Exceptions\CliActionException;
use Cube\Helpers\Cli\CliActions;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'make:helper',
    description: 'Create helper',
    help: 'This command helps generate helper'
)]
class MakeHelperCommand extends BaseCommand
{
    public function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Helper name');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');

        try {

            CliActions::buildResource(
                $name,
                CliActions::RESOURCE_TYPE_HELPER,
                false
            );
        } catch (CliActionException $e) {

            $output->writeln([
                '<fg=red>Unable to create helper</>',
                concat('<fg=red>', $e->getMessage(), '</>')
            ]);

            return self::FAILURE;
        }

        $output->writeln(
            concat('<info>helper ', $name, ' created successfully</info>')
        );

        return self::SUCCESS;
    }
}
