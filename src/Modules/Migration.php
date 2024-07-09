<?php

namespace Cube\Modules;

use Cube\Interfaces\MigrationInterface;
use Cube\Modules\Db\DBConnection;
use Cube\Modules\Db\DBConnector;
use Cube\Modules\Db\DBTable;

abstract class Migration implements MigrationInterface
{
    protected static string $name;

    protected static string $connection = DBConnector::DEFAULT_CONNECTION_NAME;

    /**
     * Database table
     *
     * @return DBTable
     */
    protected static function getTable(): DBTable
    {
        return new DBTable(
            static::$name,
            static::getConnection()
        );
    }

    /**
     * Database connection
     *
     * @return DBConnection
     */
    protected static function getConnection(): DBConnection
    {
        return DBConnection::connection(static::$connection);
    }
}
