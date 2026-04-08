<?php

namespace Cube\Commands\Session;

use Cube\Commands\BaseCommand;
use Cube\Modules\Sessions\DBSessionManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'session:db:migrate',
    description: 'Create db migration for session',
    help: 'This command creates the session table'
)]
class SessionDatabaseMigrateCommand extends BaseCommand
{
    public function configure(): void
    {
        $this
            ->setHelp('This command creates the session table');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<fg=yellow>Setting up your app......</>');
        (new DBSessionManager())->init();

        $output->writeln(
            '<info>[✓]Session migration completed</info>'
        );

        return self::SUCCESS;
    }
}
