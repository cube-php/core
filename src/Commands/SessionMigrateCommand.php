<?php

namespace Cube\Commands;

use Cube\Modules\SessionManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SessionMigrateCommand extends BaseCommand
{
    protected static $defaultName = 'session:migrate';

    public function configure()
    {
        $this
            ->setDescription('Create db migration for session')
            ->setHelp('This command creates the session table');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<fg=yellow>Setting up your app......</>');
        
        (new SessionManager)->init();

        $output->writeln(
            '<info>[✓]Session migration completed</info>'
        );

        return self::SUCCESS;
    }
}