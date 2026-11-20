<?php

namespace Cube\Modules\Db;

use Cube\Modules\Db\DBQueryBuilder;

class DBDelete extends DBQueryBuilder
{
    use DBWriteJoins;

    private DBTable $table;

    /**
     * Class constructor
     * 
     * @param DBTable $table
     */
    public function __construct(DBTable $table)
    {
        $this->table = $table;
        $this->joinSql($this->writeQueryHeader());
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

    /**
     * Return the original statement header used to locate the join insertion point.
     *
     * @return string
     */
    protected function writeQueryHeader(): string
    {
        return 'DELETE FROM ' . $this->table->name;
    }

    /**
     * Target only the base table in MySQL's joined DELETE syntax.
     *
     * Joined tables select matching rows but are not themselves deleted from.
     *
     * @return string
     */
    protected function joinedQueryHeader(): string
    {
        return 'DELETE ' . $this->table->name . ' FROM ' . $this->table->name;
    }
}
