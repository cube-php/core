<?php

namespace Cube\Modules\Db;

use PDO;
use PDOStatement;
use PDOException;

use Cube\App\App;
use Cube\Exceptions\DBException;

class DBConnection
{
    /**
     * Set whether database has initiated connection
     *
     * @var boolean
     */
    private static $_connected = false;

    /**
     * Database instance
     * 
     * @var \PDO
     */
    private static $_instance = null;

    /**
     * Database connection
     * 
     * @var \PDO
     */
    private $connection;

    /**
     * Database configuration
     * 
     * @var array
     */
    private $config;

    /**
     * Class constructor
     * 
     * Creates the PDO connection
     */
    private function __construct()
    {
        $config = $this->config = App::getConfig('database');
        $options = $config['options'] ?? [];

        if(!is_array($options)) {
            throw new DBException('Invalid database configuration options');
        }
        
        $driver = $config['driver'] ?? '';
        $hostname = $config['hostname'] ?? '';

        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';
        
        $dbname = $config['dbname'] ?? '';
        $charset = $config['charset'] ?? '';
        $port = $config['port'] ?? '';

        $dsn = "{$driver}:host={$hostname};dbname={$dbname};charset={$charset}";

        if($port) {
            $dsn .= ";port={$port}";
        }

        $default_opts = array(
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        );

        if(count($options)) {
            every($options, function($value, $key) use (&$default_opts) {
                $default_opts[$key] = $value;
            });
        }
        
        try {

            $this->connection = new PDO($dsn, $username, $password, $default_opts);
            static::$_connected = true;

        } catch (PDOException $e) {
            throw new DBException('Unable to establish database connection. Error: "' . $e->getMessage() . '"', 500);
        }
    }

    /**
     * Prevent duplication of connection
     * 
     */
    private function __clone() { }

    /**
     * Get database instance
     * 
     * @return self
     */
    public static function getInstance()
    {
        if(!static::$_instance) {
            static::$_instance = new self;
        }
        
        return static::$_instance;
    }

    /**
     * Close connection
     *
     * @return void
     */
    public function close()
    {
        $this->connection = null;
        static::$_connected = false;
    }

    /**
     * Return config
     * 
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }
    
    /**
     * Return connection
     * 
     * @return \PDO
     */
    public function getConnection()
    {
        return $this->connection;
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
        switch($item)
        {
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
     * @return \PDOStatement
     */
    public function query($sql, array $params = [])
    {
        if(!$this->connection) {
            throw new DBException('Connection failed');
        }

        $stmt = $this->connection->prepare($sql);
        
        if(count($params)) {

            foreach($params as $index => &$value)
            {
                $index++;
                $stmt->bindValue($index, $value, $this->getDataType($value));
            }
        }

        $stmt->execute();
        return $stmt;
    }

    /**
     * Returns whether database is connected
     *
     * @return boolean
     */
    public static function isConnected()
    {
        return static::$_connected;
    }
}