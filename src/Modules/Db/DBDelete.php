<?php

namespace Cube\Modules\Db;

use Cube\Modules\Db\DBQueryBuilder;

class DBDelete extends DBQueryBuilder
{
    private DBTable $table;

    /**
     * Class constructor
     * 
     * @param DBTable $table
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

    /**
     * Normalize table field names before adding them to SQL.
     *
     * @param string $field Field name
     * @return string
     */
    protected function fieldName(string $field): string
    {
        return $this->table->getDatabaseField($field);
    }
}
