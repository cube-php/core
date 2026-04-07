<?php

namespace Cube\Commands;

use Cube\Modules\SessionManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'session:migrate',
    description: 'Create db migration for session',
    help: 'This command creates the session table'
)]
class SessionMigrateCommand extends BaseCommand
{
    public function configure(): void
    {
        $this
            ->setHelp('This command creates the session table');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<fg=yellow>Setting up your app......</>');

        (new SessionManager)->init();

        $output->writeln(
            '<info>[✓]Session migration completed</info>'
        );

        return self::SUCCESS;
    }
}
