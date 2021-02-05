<?php

namespace Cube\Commands;

use Cube\Misc\EventManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EventDispatcherCommand extends BaseCommand
{
    protected static $defaultName = 'dispatch:event';

    public function configure()
    {
        $this
            ->setDescription('Dispatch event via terminal')
            ->setHelp('This command helps dispatch event via terminal')
            ->addArgument('name', InputArgument::REQUIRED, 'Event name')
            ->addOption('args', 'a', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Arguments', []);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $args = $input->getOption('args');

        if(!EventManager::hasAttachedEvents($name)) {
            $output->writeln(
                concat('<fg=red>No event listeners registered for ', $name, '</>')
            );
            return Command::FAILURE;
        }

        EventManager::dispatchEvent($name, $args);
        return Command::SUCCESS;
    }
}