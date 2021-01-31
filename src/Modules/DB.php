<?php

namespace Cube\Modules;

use Cube\Modules\Db\DBTable;
use Cube\Modules\Db\DBConnection;

class DB
{

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
    public static function statement($query, array $params = [])
    {
        return static::conn()->query($query, $params);
    }

    /**
     * Escape injection characters
     * 
     * @param string $string String to escaped
     * 
     * @return string
     */
    public static function escape($string)
    {
        return static::conn()->getConnection()->quote($string);
    }

    /**
     * Get last insert id
     * 
     * @return string
     */
    public static function lastInsertId()
    {
        return (int) static::conn()->getConnection()->lastInsertId();
    }

    /**
     * Run table specific queries
     * 
     * @param string $table_name
     * 
     * @return DBTable
     */
    public static function table($table_name)
    {
        return new DBTable($table_name);
    }

    /**
     * Check if database has table
     * 
     * @param string $name Table name
     * 
     * @return bool
     */
    public static function hasTable($name)
    {
        return !!in_array($name, static::tables());
    }

    /**
     * List all tables in database
     * 
     * @return string[]
     */
    public static function tables()
    {
        $dbname = static::conn()->getConfig()['dbname'];

        $query = static::statement('SELECT table_name FROM information_schema.tables WHERE table_schema = ?', [$dbname]);
        
        if(!$query->rowCount()) return array();

        $results = $query->fetchAll();
        $data = array();

        foreach($results as $fetched_table)
        {
            $data[] = $fetched_table->table_name;
        }

        return $data;
    }

    /**
     * Database connection
     * 
     * @param
     */
    private static function conn()
    {
        return DBConnection::getInstance();
    }
}