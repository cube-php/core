<?php

namespace Cube\Modules\Db;

use Cube\Exceptions\DBException;

class DBConnection
{
    private static array $connections = array();

    /**
     * Register new db config
     *
     * @param string $name
     * @param DBConnectionConfig $config
     * @return void
     */
    public static function addConfig(string $name, DBConnectionConfig $config)
    {
        static::$connections[$name] = $config;
    }

    /**
     * Get connection by name
     *
     * @param string $name
     * @return DBConnectionConfig
     */
    public static function getConnectionByName(string $name): DBConnectionConfig
    {
        $connection = static::$connections[$name] ?? null;

        if (!$connection) {
            throw new DBException(
                concat('Database connection with name "', $name, '" not found'),
                DBException::CONNECTION_NOT_FOUND
            );
        }

        return $connection;
    }

    /**
     * Get all connections
     *
     * @return array
     */
    public function getConnections(): array
    {
        return static::$connections;
    }
}
