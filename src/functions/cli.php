<?php

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