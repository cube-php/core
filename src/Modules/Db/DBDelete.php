<?php

namespace Cube\Modules\Db;

use Cube\Modules\DB;
use Cube\Modules\Db\DBQueryBuilder;

class DBDelete extends DBQueryBuilder
{

    /**
     * Class constructor
     * 
     * @param string $table_name
     */
    public function __construct(public readonly DBTable $table)
    {
        $this->joinSql('DELETE FROM', $table->name);
    }

    /**
     * Fulfil query
     * 
     * @return int deleted rows
     */
    public function fulfil()
    {
        $query = $this
            ->table
            ->getDatabase()
            ->statement(
                $this->getSqlQuery(),
                $this->getSqlParameters()
            );

        return $query->rowCount();
    }
}
