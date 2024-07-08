<?php

namespace Cube\Modules\Db;

use Cube\Exceptions\DBException;
use PDO;
use PDOStatement;
use Throwable;

class DBConnection
{
    public function __construct(protected DBConnectorItem $item)
    {
    }

    /**
     * Return connection
     *
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->item->connection;
    }

    /**
     * Get character encoding format
     *
     * @return string
     */
    public function getCharset(): string
    {
        return $this->item->charset;
    }

    /**
     * Return whether database is connected
     *
     * @return boolean
     */
    public function isConnected(): bool
    {
        return $this->item->is_connected;
    }

    /**
     * Check if constraint exists
     *
     * @param string $constraint_name
     * @return boolean
     */
    public function constraintExists(string $constraint_name): bool
    {
        $query = $this->query(
            DBWordConstruct::constraintsExist(),
            [$constraint_name]
        )->fetch();

        return !!$query->tcount;
    }

    /**
     * Return PDO Param type based on data type
     * 
     * @param 
     * 
     * @return int
     */
    public function getDataType($item)
    {
        switch ($item) {
            case is_bool($item):
                return PDO::PARAM_BOOL;
                break;

            case is_int($item):
                return PDO::PARAM_INT;
                break;

            case is_null($item):
                return PDO::PARAM_NULL;
                break;

            default:
                return PDO::PARAM_STR;
                break;
        }
    }

    /**
     * Database query runner
     * 
     * @param string $sql Query to run
     * @param array $params Query parameters
     * 
     * @return PDOStatement
     */
    public function query($sql, array $params = []): PDOStatement
    {
        if (!$this->item->is_connected) {
            throw new DBException('Database connection failed');
        }

        $connection = $this->getConnection();
        $stmt = $connection->prepare($sql);

        if (count($params)) {
            every($params, function ($value, $index) use ($stmt) {
                $stmt->bindValue($index + 1, $value, $this->getDataType($value));
            });
        }

        $stmt->execute();
        return $stmt;
    }

    /**
     * Escape string
     *
     * @param string $string
     * @return string
     */
    public function escape(string $string)
    {
        return $this->getConnection()->quote($string);
    }

    /**
     * Get last insert id
     *
     * @return int
     */
    public function lastInsertId(): int
    {
        return (int) $this->getConnection()->lastInsertId();
    }

    /**
     * List all tables
     *
     * @return string[]
     */
    public function tables()
    {
        $dbname = $this->item->dbname;
        $query = $this->query(
            DBWordConstruct::selectTables(),
            [$dbname]
        );

        if (!$query->rowCount()) {
            return array();
        }

        $results = $query->fetchAll();
        $data = array();

        every($results, function ($result) use (&$data) {
            $table = (array) $result;
            $table_data = array_change_key_case($table, CASE_LOWER);
            $data[] = $table_data['table_name'];
        });

        return $data;
    }

    /**
     * Check if table exists in database
     *
     * @param string $name
     * @return boolean
     */
    public function hasTable(string $name)
    {
        return !!in_array($name, $this->tables());
    }

    /**
     * Start database transaction
     *
     * @return void
     */
    public function startTransaction()
    {
        $this->query('START TRANSACTION;');
    }

    /**
     * Commit database transaction
     *
     * @return void
     */
    public function commit()
    {
        $this->query('COMMIT;');
    }

    /**
     * Rollback database transaction
     *
     * @return void
     */
    public function rollback()
    {
        $this->query('ROLLBACK;');
    }

    /**
     * Run database transaction operation
     * Auto rollback when an exception is thrown and auto commit if not
     *
     * @param callable $fn
     * @return mixed
     */
    public function transaction(callable $fn): mixed
    {
        $this->startTransaction();

        try {
            $result = $fn();
        } catch (Throwable $e) {
            $this->rollback();
            throw $e;
        }

        $this->commit();
        return $result;
    }

    /**
     * Get connection from nanme
     *
     * @param string $name
     * @return self
     */
    public static function connection(?string $name = null): self
    {
        return new self(
            item: DBConnector::connection(
                $name ?? DBConnector::DEFAULT_CONNECTION_NAME
            )
        );
    }
}
