<?php

namespace Cube\Commands;

use Cube\App\Directory;
use Cube\Exceptions\CliActionException;
use Cube\Helpers\Cli\CliActions;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeHelperCommand extends BaseCommand
{
    protected static $defaultName = 'make:helper';

    public function configure()
    {
        $this
            ->setDescription('Create helper')
            ->setHelp('This command helps generate helper')
            ->addArgument('name', InputArgument::REQUIRED, 'Helper name');
    }

    public function execute(InputInterface $input, OutputInterface $output)
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
            concat('<info>controller', $name ,' created successfully</info>')
        );
        
        return self::SUCCESS;
    }
}