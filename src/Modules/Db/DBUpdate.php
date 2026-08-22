<?php

namespace Cube\Modules\Db;

use Cube\Modules\Db\DBQueryBuilder;

class DBUpdate extends DBQueryBuilder
{
    private DBTable $table;

    /**
     * Constructor
     * 
     * @param DBTable $table
     */
    public function __construct(DBTable $table)
    {
        $this->table = $table;
        $this->joinSql('UPDATE', $table->name);
    }

    /**
     * Create insert entry
     * 
     * @param string[] $params
     * @return self
     */
    public function entry($params): self
    {
        $params[$this->table->getDatabaseField('updated_at')] = getnow();
        $this->make($params);
        return $this;
    }

    /**
     * Query executor
     * 
     * @return int
     */
    public function fulfil(): int
    {
        $db = $this->table->connection->query(
            $this->getSqlQuery(),
            $this->getSqlParameters()
        );

        return $db->rowCount();
    }

    /**
     * Make query
     * 
     * @param string[] $params Parameters to make query from
     * @return void
     */
    private function make($params)
    {
        $keys = array_keys($params);
        $values = array_values($params);
        $placeholders = [];

        foreach ($keys as $key) $placeholders[] = $this->table->getDatabaseField($key) . ' = ?';

        $fields = implode(',', $placeholders);
        $this->bindParam($values);
        $this->joinSql(null, 'SET', $fields);
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
