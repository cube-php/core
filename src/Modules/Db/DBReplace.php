<?php

namespace Cube\Modules\Db;

use Cube\Modules\DB;
use Cube\Modules\Db\DBQueryBuilder;

class DBReplace extends DBQueryBuilder
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
        $this->joinSql('REPLACE INTO', $table->name);
    }

    /**
     * Create insert entry
     * 
     * @param string[] $params
     * 
     * @return int
     */
    public function entry($params)
    {
        $this->make($params);
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
        $connection->query(
            $this->getSqlQuery(),
            $this->getSqlParameters()
        );

        return $connection->lastInsertId();
    }

    /**
     * Make query
     * 
     * @param string[] $params Parameters to make query from
     * 
     * @return 
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

        #Add placeholders tp query
        $placeholders = implode(', ', $placeholders_vars);
        $this->joinSql(null, '(', $placeholders, ')');
    }
}
