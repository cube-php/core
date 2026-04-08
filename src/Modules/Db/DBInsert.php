<?php

namespace Cube\Modules\Db;

use Cube\Modules\Db\DBQueryBuilder;

class DBInsert extends DBQueryBuilder
{

    private DBTable $table;

    /**
     * Constructor
     * 
     * @param string $table_name
     */
    public function __construct(DBTable $table)
    {
        $this->table = $table;
        $this->joinSql('INSERT INTO', $table->name);
    }

    /**
     * Create insert entry
     * 
     * @param string[] $params
     * 
     * @return int
     */
    public function entry($params, array $on_duplicate = [])
    {
        $this->make(
            array_merge(
                $on_duplicate,
                $params,
                ['created_at' => getnow()]
            )
        );

        if ($on_duplicate !== null) {
            $this->onDuplicate($on_duplicate, $params);
        }

        return $this->finish();
    }

    /**
     * Query executor
     * 
     * @return
     */
    private function finish()
    {
        $connection = $this->table->connection;
        $connection->query($this->getSqlQuery(), $this->getSqlParameters());
        return $connection->lastInsertId();
    }

    /**
     * Make query
     * 
     * @param string[] $params Parameters to make query from
     * 
     * @return void
     */
    private function make($params)
    {
        $keys = array_keys($params);
        $fields = implode(', ', $keys);

        $parameters = array_values($params);
        $this->bindParam($parameters);

        #Add keys as fields to query
        $this->joinSql(null, '(', $fields, ')', 'VALUES');

        #Placeholders
        $keys_count = count($keys);
        $placeholders_vars = array_fill(0, $keys_count, '?');

        #Add placeholders to query
        $placeholders = implode(', ', $placeholders_vars);
        $this->joinSql(null, '(', $placeholders, ')');
    }

    private function onDuplicate(array $insert_params, array $params): void
    {
        $updates = [];

        if (!$params) {
            $params = array_values(
                array_filter(
                    array_keys($insert_params),
                    fn($key) => $key !== 'created_at'
                )
            );
        }

        foreach ($params as $key => $value) {
            if (is_int($key)) {
                $updates[] = "{$value} = VALUES({$value})";
                continue;
            }

            $updates[] = "{$key} = ?";
            $this->addParam($value);
        }

        if (!$updates) {
            return;
        }

        $this->joinSql(null, 'ON DUPLICATE KEY UPDATE', implode(', ', $updates));
    }
}
