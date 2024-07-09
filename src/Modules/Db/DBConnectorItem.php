<?php

namespace Cube\Modules\Db;

use PDO;

readonly class DBConnectorItem
{
    public function __construct(
        public string $name,
        public bool $is_connected,
        public PDO $connection,
        public string $dbname,
        public string $username,
        public string $charset,
    ) {
    }
}
