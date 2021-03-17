<?php

namespace Cube\Commands;

use Cube\Interfaces\ConsoleInterface;
use ReflectionClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleCommand extends BaseCommand
{
    protected static $defaultName = 'run:console-command';

    public function configure()
    {
        $this
            ->setDescription('Run a console command')
            ->setHelp('This command helps run console commands')
            ->addArgument('name', InputArgument::REQUIRED, 'console command name')
            ->addOption(
                'arguments',
                'a',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'Arguments',
                []
            );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $args = $input->getOption('arguments');

        $config = $this->app->getConfig('console');

        if(!$config) {
            $output->writeln('<fg=red>Console not setup</>');
            return Command::FAILURE;
        }

        $console = $config[$name] ?? null;

        if(!$console) {
            $output->writeln('<fg=red>', $name, 'console command is unregistered</>');
            return Command::FAILURE;
        }

        $reflection = new ReflectionClass($console);

        if(!$reflection->implementsInterface(ConsoleInterface::class)) {
            $output->writeln(
                concat('<fg=red>', $name, 'does not implement console class</>')
            );
            return Command::FAILURE;
        }

        $instance = new $console($this->app);
        $response = $instance->onCall($args);

        $output->writeln($response);
        return Command::SUCCESS;
    }
}