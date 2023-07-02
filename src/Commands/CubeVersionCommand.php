<?php

namespace Cube\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CubeVersionCommand extends BaseCommand
{
    protected static $defaultName = 'version';

    public function configure()
    {
        $this
            ->setDescription('Get current core version');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $dir = __DIR__ . '/../../composer.json';

        if (!file_exists($dir)) {
            $output->writeln('Unable to get version');
            return Command::FAILURE;
        }

        $content = json_decode(
            file_get_contents($dir)
        );

        $description = $content->description ?? null;
        $version = $content->version ?? null;

        $output->writeln(
            concat('<fg=yellow>', $description, '</>')
        );
        $output->writeln(
            concat('<fg=green>Version: ', $version, '</>')
        );

        return Command::SUCCESS;
    }
}
