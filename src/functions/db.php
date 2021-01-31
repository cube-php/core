<?php

use Cube\Modules\Db\DBSelect;

/**
 * Multi query
 *
 * @param array $queries
 * @param callable $fn
 * @return DBSelect[]
 */
function multi_query(array $queries, callable $fn) {
    return every($queries, function ($query) use ($fn) {
        $fn($query);
        return $query;
    });
}