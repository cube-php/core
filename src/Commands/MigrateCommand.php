<?php

namespace Cube\Commands;

use Cube\App\Directory;
use Cube\Helpers\Cli\CliActions;
use Cube\Misc\EventManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCommand extends BaseCommand
{
    protected static $defaultName = 'migrate';

    public function configure()
    {
        $this
            ->setDescription('Manage migrations')
            ->setHelp('Run migration commands')
            ->addArgument('name', InputArgument::OPTIONAL, 'Migration name', null)
            ->addOption('action', '-a', InputOption::VALUE_OPTIONAL, 'Migration down', 'up');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $raw_name = $input->getArgument('name');
        $action = $input->getOption('action');

        EventManager::dispatchEvent(
            EventManager::onBeforeMigrate,
            [$raw_name, $action]
        );

        $name_required_actions = array('down', 'empty');
        $actions = array_merge($name_required_actions, ['up']);

        if(in_array($action, $name_required_actions) && !$raw_name) {
            $output->writeln(
                concat('<fg=red>Migration name is required to perform "', $action, '" action </>')
            );

            return Command::FAILURE;
        }

        if(!$action || !in_array($action, $actions)) {
            $output->writeln(
                concat('<fg=red>', 'Invalid action', '</>')
            );

            return Command::FAILURE;
        }

        $path = concat($this->app->getPath(Directory::PATH_APP), '/Migrations');
        $files = scandir($path);
        $count = 0;

        array_walk($files, function ($file) use ($path, $output, $action, $raw_name, &$count) {

            $name = CliActions::getSyntaxedName($raw_name, 'Migration');
            $filepath = concat($path, '/', $file);
            $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

            $class = pathinfo($filepath, PATHINFO_FILENAME);
            $class_name = concat('App\Migrations\\', $class);

            if('php' !== $ext) {
                return;
            }

            if($raw_name && $class !== $name) {
                return;
            }

            $class_name::$action();
            $output->writeln(concat('<info>', '[✓] ', $class, '</info>'));
            $count++;
        });

        $output->writeln(concat('<info>', $count, ' migrations completed</info>'));
        return Command::SUCCESS;
    }
}