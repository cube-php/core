<?php

namespace Cube\Commands;

use Cube\Exceptions\AppException;
use Cube\Modules\System;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AppSetupCommand extends BaseCommand
{
    protected static $defaultName = 'app:setup';

    public function configure()
    {
        $this
            ->setDescription('Run app setup')
            ->setHelp('This command helps prepare and setup required components');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<fg=yellow>Setting up your app......</>');
        $system = new System();
        
        try {
            $system->init();
        } catch(AppException $e) {
            $output->writeln(
                concat('<fg=red>', $e->getMessage() ,'</>')
            );

            return self::FAILURE;
        }

        $output->writeln(
            '<info>[✓]Setup completed</info>'
        );

        return self::SUCCESS;
    }
}