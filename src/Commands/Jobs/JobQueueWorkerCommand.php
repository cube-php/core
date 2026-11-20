<?php

namespace Cube\Commands\Jobs;

use Cube\Commands\BaseCommand;
use Cube\Queue\Queue;
use Cube\Queue\Worker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Exception\InvalidArgumentException;
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
            ->addOption('group', '-g', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Only process these groups (repeatable or comma-separated)')
            ->addOption('exclude-group', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Exclude these groups (repeatable or comma-separated; exclusions take precedence)')
            ->addOption('sleep', '-s', InputOption::VALUE_OPTIONAL, 'Seconds to sleep when no job is available', 1)
            ->addOption('workers', '-w', InputOption::VALUE_OPTIONAL, 'Number of worker processes to spawn', 1)
            ->addOption('managed', '-m', InputOption::VALUE_OPTIONAL, 'Whether the worker is managed by the queue manager', false);
    }

    /**
     * Normalize repeated, comma-separated group options into unique names.
     *
     * @param string[] $values
     * @return string[]
     * @throws InvalidArgumentException When a group name is empty.
     */
    protected function parseGroups(array $values): array
    {
        $groups = [];
        foreach ($values as $value) {
            foreach (explode(',', $value) as $group) {
                $group = trim($group);
                if ($group === '') {
                    throw new InvalidArgumentException('Group names must not be empty.');
                }
                $groups[] = $group;
            }
        }
        return array_values(array_unique($groups));
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $groups = $this->parseGroups($input->getOption('group'));
        $excludedGroups = $this->parseGroups($input->getOption('exclude-group'));
        $sleep = (int) $input->getOption('sleep');
        $managed = (bool) $input->getOption('managed');

        $output->writeln(
            '<fg=yellow>Worker listening for jobs [' . ($groups ? implode(', ', $groups) : 'all') . ($excludedGroups ? '; excluding ' . implode(', ', $excludedGroups) : '') . ', ' . ($managed ? 'managed' : 'unmanaged') . ']......</>'
        );

        (new Worker(
            queue: new Queue(groups: $groups, excludedGroups: $excludedGroups),
            sleep: $sleep,
            managed: $managed,
        ))->work();
        return BaseCommand::SUCCESS;
    }
}
