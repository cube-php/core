<?php

namespace Cube\Commands\Jobs;

use Cube\App\App;
use Cube\Commands\BaseCommand;
use Cube\Queue\Queue;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'jobs:queue:manage',
    description: 'Manage job queue workers',
    help: 'This command starts a worker to process jobs from the queue.'
)]
class JobQueueManagerCommand extends BaseCommand
{
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(
            '<fg=yellow>Job queue management started......</>'
        );

        $min_workers = App::getConfig('queue.min_workers', 1);
        $max_workers = App::getConfig('queue.max_workers', 5);
        $current_workers = [];

        while (true) {
            $pending = (new Queue())->getPendingJobsCount();
            $target_workers = min(
                $max_workers,
                max($min_workers, (int) ceil($pending / 50))
            );

            while (count($current_workers) < $target_workers) {
                $output->writeln(
                    '<fg=green>Spawning new worker process. Current: ' . count($current_workers) . ', Target: ' . $target_workers . '</>'
                );

                $proc = proc_open(
                    [env('CLI_PHP_PATH', 'php'), 'cube', 'jobs:queue:work', '-m', '1'],
                    [],
                    $pipes
                );
                $current_workers[] = $proc;
            }

            sleep(5);
        }

        return BaseCommand::SUCCESS;
    }
}
