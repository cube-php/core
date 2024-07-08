<?php

namespace Cube\Modules\Db;

use Cube\App\App;
use Cube\Exceptions\DBException;
use PDO;
use PDOException;

class DBConnector
{
    public const DEFAULT_CONNECTION_NAME = 'mysql';

    protected static array $connections = array();

    /**
     * Fetch connection
     *
     * @param string $name
     * @return DBConnectorItem
     */
    public static function connection(string $name = self::DEFAULT_CONNECTION_NAME): DBConnectorItem
    {
        if (isset(static::$connections[$name])) {
            return static::$connections[$name];
        }

        $config = App::getConfig('database');

        if (!$config || !is_array($config)) {
            throw new DBException('Invalid database configuration');
        }

        $options = $config[$name] ?? null;

        if (!$options) {
            throw new DBException(
                sprintf('Database configuration for "%s" not found', $name)
            );
        }

        $driver = $options['driver'] ?? null;
        $hostname = $options['hostname'] ?? null;
        $username = $options['username'] ?? null;
        $password = isset($options['password']) ? $options['password'] : null;
        $dbname = $options['dbname'] ?? null;
        $charset = $options['charset'] ?? 'utf-8';
        $port = $options['port'] ?? '3306';
        $custom_options = $options['options'] ?? [];

        if (!$driver) {
            throw new DBException(
                sprintf('Driver not set for database configuration "%s"', $name)
            );
        }

        if (!$hostname) {
            throw new DBException(
                sprintf('Hostname not set for database configuration "%s"', $name)
            );
        }

        if (!$username) {
            throw new DBException(
                sprintf('Username not set for database configuration "%s"', $name)
            );
        }

        if (!$dbname) {
            throw new DBException(
                sprintf('DB name not set for database configuration "%s"', $name)
            );
        }

        $dsn = "{$driver}:host={$hostname};dbname={$dbname};charset={$charset};port={$port}";

        $default_options = array(
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        );

        every($custom_options, function ($value, $key) use (&$default_options) {
            $default_options[$key] = $value;
        });

        try {
            $connection = new PDO(
                options: $default_options,
                username: $username,
                password: $password,
                dsn: $dsn,
            );
        } catch (PDOException $e) {
            throw new DBException(
                'Unable to establish database connection for "' . $name . '" ' . $e->getMessage()
            );
        }

        $item = new DBConnectorItem(
            connection: $connection,
            username: $username,
            is_connected: true,
            charset: $charset,
            dbname: $dbname,
            name: $name
        );

        static::$connections[$name] = $item;
        return $item;
    }

    public function close()
    {
    }
}
