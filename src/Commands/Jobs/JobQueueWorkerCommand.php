<?php

namespace Cube\Commands\Jobs;

use Cube\Commands\BaseCommand;
use Cube\Queue\Queue;
use Cube\Queue\Worker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'jobs:queue:work',
    description: 'Start processing jobs on the queue as a daemon',
)]
class JobQueueWorkerCommand extends BaseCommand
{
    public function configure(): void
    {
        $this
            ->addOption('group', '-g', InputOption::VALUE_OPTIONAL, 'The job group to process')
            ->addOption('sleep', '-s', InputOption::VALUE_OPTIONAL, 'Seconds to sleep when no job is available', 1)
            ->addOption('workers', '-w', InputOption::VALUE_OPTIONAL, 'Number of worker processes to spawn', 1)
            ->addOption('managed', '-m', InputOption::VALUE_OPTIONAL, 'Whether the worker is managed by the queue manager', false);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $group = $input->getOption('group');
        $sleep = (int) $input->getOption('sleep');
        $managed = (bool) $input->getOption('managed');

        $output->writeln(
            '<fg=yellow>Worker listening for jobs [' . ($group ?? 'all') . ', ' . ($managed ? 'managed' : 'unmanaged') . ']......</>'
        );

        (new Worker(
            queue: new Queue($group),
            sleep: $sleep,
            managed: $managed,
        ))->work();
        return BaseCommand::SUCCESS;
    }
}
