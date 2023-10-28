<?php

namespace Cube\Modules\Db;

use Cube\Exceptions\DBException;
use Cube\Traits\Onceable;
use PDOException;
use PDO;

class DBConnectionConfig
{
    use Onceable;

    private bool $is_connected = false;

    public function __construct(
        public readonly string $driver,
        public readonly string $hostname,
        public readonly string $username,
        public readonly string $password,
        public readonly string $dbname,
        public readonly string $port,
        public readonly string $charset
    ) {
    }

    /**
     * Get connection
     *
     * @return PDO
     */
    public function getConnection()
    {
        return $this->once(function () {
            $driver = $this->driver;
            $hostname = $this->hostname;

            $username = $this->username;
            $password = $this->password;

            $dbname = $this->dbname;
            $charset = $this->charset;
            $port = $this->port;

            $dsn = "{$driver}:host={$hostname};dbname={$dbname};charset={$charset}";

            if ($port) {
                $dsn .= ";port={$port}";
            }

            $default_opts = array(
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            );

            try {

                $connection = new PDO($dsn, $username, $password, $default_opts);
            } catch (PDOException $e) {
                throw new DBException('Unable to establish database connection. Error: "' . $e->getMessage() . '"', 500);
            }

            $this->is_connected = true;
            return $connection;
        });
    }

    public function isConnected(): bool
    {
        return $this->is_connected;
    }

    /**
     * Return PDO Param type based on data type
     * 
     * @param 
     * 
     * @return int
     */
    public function getDataType($item): int
    {
        return match (true) {
            is_bool($item) => PDO::PARAM_BOOL,
            is_null($item) => PDO::PARAM_NULL,
            is_int($item) => PDO::PARAM_INT,
            empty($item) => PDO::PARAM_STR,
            default => PDO::PARAM_STR,
        };
    }

    /**
     * Database query runner
     * 
     * @param string $sql Query to run
     * @param array $params Query parameters
     * 
     * @return \PDOStatement
     */
    public function query($sql, array $params = [])
    {
        if (!$this->getConnection()) {
            throw new DBException(
                'Connection failed',
                DBException::CONNECTION_FAILED
            );
        }

        $stmt = $this->getConnection()->prepare($sql);

        if (count($params)) {
            foreach ($params as $index => &$value) {
                $index++;
                $stmt->bindValue($index, $value, $this->getDataType($value));
            }
        }

        $stmt->execute();
        return $stmt;
    }
}
