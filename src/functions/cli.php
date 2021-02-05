<?php

use Cube\Exceptions\CubeCliException;
use Cube\Helpers\Cli\Cli;

/**
 * Run cube command
 *
 * @param string $command
 * @param boolean $run_in_background
 * @return string|bool
 */
function cube($command, bool $run_in_background = false, bool $should_wait = false) {
    return Cli::run(
        $command,
        $run_in_background,
        $should_wait
    );
}

/**
 * Run console command
 *
 * @param string $command_name
 * @param array|null $args
 * @param boolean $should_wait
 * @return string|bool
 */
function console_command(string $command_name, ?array $args = [], bool $should_wait = false) {
    $command = array(
        'run:console-command',
        $command_name,
        '-a',
        implode(' ', $args)
    );

    return cube(
        $command,
        true,
        $should_wait
    );
}