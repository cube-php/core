<?php

namespace Cube\Commands;

use Cube\App\Directory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ModelCommand extends BaseCommand
{
    protected static $defaultName = 'make:model';

    public function configure()
    {
        $this->
            setDescription('Create a model')
            ->setHelp('This command helps create a model with boilerplate')
            ->addArgument('name', InputArgument::REQUIRED, 'Model name');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $model_name = $input->getArgument('name');
        $model_path = concat($this->app->getPath(Directory::PATH_APP), '/Models');

        return Command::SUCCESS;
    }
}