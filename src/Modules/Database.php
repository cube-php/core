<?php

namespace Cube\Modules;

use Cube\Modules\Db\DBConnection;
use Cube\Modules\Db\DBTable;
use Cube\Modules\Db\DBConnectionConfig;

class Database
{
    protected DBConnectionConfig $config;

    public function __construct(string | DBConnectionConfig $connection)
    {
        $this->config = is_string($connection)
            ? DBConnection::getConnectionByName(
                $connection
            )
            : $connection;
    }

    /**
     * Run a database query
     * 
     * @param string $query Query to run
     * @param string[] $params Query parameters
     * 
     * @return \PDOStatement
     * 
     * @throws \InvalidArgumentException
     */
    public function statement($query, array $params = [])
    {
        return $this->config->query($query, $params);
    }

    /**
     * Check if constraint exists
     *
     * @param string $constraint_name
     * @return boolean
     */
    public function constraintExists(string $constraint_name): bool
    {
        $query = $this->statement(
            'SELECT count(CONSTRAINT_NAME) tcount FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_NAME = ?',
            [$constraint_name]
        )->fetch();

        return !!$query->tcount;
    }

    /**
     * Escape injection characters
     * 
     * @param string $string String to escaped
     * 
     * @return string
     */
    public function escape($string)
    {
        return $this->config->getConnection()->quote($string);
    }

    /**
     * Get last insert id
     * 
     * @return int
     */
    public function lastInsertId()
    {
        return (int) $this->config->getConnection()->lastInsertId();
    }

    /**
     * Run table specific queries
     * 
     * @param string $table_name
     * 
     * @return DBTable
     */
    public function table($table_name)
    {
        return new DBTable($table_name, $this->config);
    }

    /**
     * Check if database has table
     * 
     * @param string $name Table name
     * 
     * @return bool
     */
    public function hasTable($name)
    {
        return !!in_array($name, $this->tables());
    }

    /**
     * List all tables in database
     * 
     * @return string[]
     */
    public function tables()
    {
        $dbname = $this->config->dbname;
        $query = $this->statement(
            'SELECT table_name FROM information_schema.tables WHERE table_schema = ?',
            [$dbname]
        );

        if (!$query->rowCount()) return array();

        $results = $query->fetchAll();
        $data = array();

        array_walk($results, function ($fetched_table) use (&$data) {
            $fetched_table = (array) $fetched_table;
            $table_data = array_change_key_case($fetched_table, CASE_LOWER);
            $data[] = $table_data['table_name'];
        });

        return $data;
    }

    /**
     * Get new instance of self
     * @param string|DBConnectionConfig $config 
     * @return Database 
     */
    public static function from(string | DBConnectionConfig $config)
    {
        return new self($config);
    }
}
