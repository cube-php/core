<?php

use Cube\Helpers\Cli\Cli;

/**
 * Run cube command
 *
 * @param string $command
 * @param boolean $run_in_background
 * @return string|bool
 */
function cube($command, bool $run_in_background = false) {
    return Cli::run(
        $command,
        $run_in_background
    );
}

/**
 * Run console command
 *
 * @param string $command_name
 * @param array|null $args
 * @param boolean $run_in_background
 * @return string|bool
 */
function console_command(string $command_name, ?array $args = [], bool $run_in_background = false) {
    $command = array(
        'run:console-command',
        $command_name,
        '-a',
        $args ? implode(' ', $args) : null
    );

    return cube($command, $run_in_background);
}