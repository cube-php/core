<?php

namespace Cube\Commands\Jobs;

use Cube\Commands\BaseCommand;
use Cube\Queue\Queue;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'jobs:dispatch',
    description: 'Dispatch a job to the queue',
    help: 'This command allows you to dispatch a job to the queue for processing.'
)]
class JobDispatchCommand extends BaseCommand
{
    public function configure(): void
    {
        $this->addArgument('id', InputArgument::REQUIRED, 'Job Id');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = (int) $input->getArgument('id');
        $job = Queue::findJob($id);

        if (!$job) {
            $output->writeln('<fg=red>Job with ID ' . $id . ' not found.</>');
            return BaseCommand::FAILURE;
        }

        try {
            $payload = unserialize($job->payload);
            $payload->handle();
            (new Queue())->delete($job);
        } catch (Throwable $e) {
        }

        return BaseCommand::SUCCESS;
    }
}
