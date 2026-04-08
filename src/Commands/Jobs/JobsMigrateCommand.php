<?php

namespace Cube\Commands\Jobs;

use Cube\Commands\BaseCommand;
use Cube\Queue\Migrations\JobsMigration;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'jobs:db:migrate',
    description: 'Create db migration for jobs',
    help: 'This command creates the jobs table'
)]
class JobsMigrateCommand extends BaseCommand
{
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<fg=yellow>Setting up your app......</>');
        JobsMigration::up();

        $output->writeln(
            '<info>[✓]Jobs migration completed</info>'
        );

        return self::SUCCESS;
    }
}
