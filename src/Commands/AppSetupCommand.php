<?php

namespace Cube\Commands;

use Cube\Exceptions\AppException;
use Cube\Misc\EventManager;
use Cube\Modules\System;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:setup',
    description: 'Run app setup',
    help: 'This command helps prepare and setup required components'
)]
class AppSetupCommand extends BaseCommand
{
    public function configure(): void
    {
        $this
            ->setHelp('This command helps prepare and setup required components');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<fg=yellow>Setting up your app......</>');

        EventManager::dispatchEvent(
            EventManager::onBeforeMigrate,
            [null, 'up']
        );

        $system = new System();

        try {
            $system->init();
        } catch (AppException $e) {
            $output->writeln(
                concat('<fg=red>', $e->getMessage(), '</>')
            );

            return self::FAILURE;
        }

        $output->writeln(
            '<info>[✓]Setup completed</info>'
        );

        return self::SUCCESS;
    }
}
