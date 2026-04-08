<?php

namespace Cube\Commands;

use Cube\Misc\EventManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'dispatch:event',
    description: 'Dispatch event via terminal',
    help: 'This command helps dispatch event via terminal',
)]
class EventDispatcherCommand extends BaseCommand
{
    public function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Event name')
            ->addOption('args', 'a', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Arguments', []);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $args = $input->getOption('args');

        if (!EventManager::hasAttachedEvents($name)) {
            $output->writeln(
                concat('<fg=red>No event listeners registered for ', $name, '</>')
            );
            return Command::FAILURE;
        }

        EventManager::dispatchEvent($name, $args);
        return Command::SUCCESS;
    }
}
