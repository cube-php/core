<?php

namespace Cube\Modules\Db;

use Cube\Modules\Db\DBQueryBuilder;

class DBDelete extends DBQueryBuilder
{
    private DBTable $table;

    /**
     * Class constructor
     * 
     * @param string $table_name
     */
    public function __construct(DBTable $table)
    {
        $this->table = $table;
        $this->joinSql('DELETE FROM', $table->name);
    }

    /**
     * Fulfil query
     * 
     * @return int deleted rows
     */
    public function fulfil()
    {
        $query = $this->table->connection->query(
            $this->getSqlQuery(),
            $this->getSqlParameters()
        );

        return $query->rowCount();
    }
}
