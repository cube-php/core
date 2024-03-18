<?php

namespace Cube\Modules;

use Cube\Modules\Db\DBTable;
use Cube\Modules\Db\DBConnection;
use Throwable;

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
     * Check if constraint exists
     *
     * @param string $constraint_name
     * @return boolean
     */
    public static function constraintExists(string $constraint_name): bool
    {
        $query = DB::statement(
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
     * Start database transaction
     *
     * @return void
     */
    public static function startTransaction()
    {
        DB::statement('START TRANSACTION;');
    }

    /**
     * Commit database transaction
     *
     * @return void
     */
    public static function commit()
    {
        DB::statement('COMMIT;');
    }

    /**
     * Rollback database transaction
     *
     * @return void
     */
    public static function rollback()
    {
        DB::statement('ROLLBACK;');
    }

    /**
     * Run database transaction operations
     *
     * @param callable $fn
     * @return mixed
     */
    public static function transaction(callable $fn): mixed
    {
        self::startTransaction();

        try {

            $result = $fn();
        } catch (Throwable $e) {
            self::rollback();
            throw $e;
        }

        self::commit();
        return $result;
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
