<?php

namespace Cube\Modules\Db;

use PDOStatement;
use Cube\Http\Env;
use Cube\Modules\Database;
use Cube\Modules\Db\DBDelete;
use Cube\Modules\Db\DBInsert;
use Cube\Modules\Db\DBReplace;
use Cube\Modules\Db\DBSelect;
use Cube\Modules\Db\DBUpdate;
use Cube\Modules\Db\DBSchemaBuilder;
use Cube\Modules\Db\DBTableBuilder;
use Cube\Modules\Db\DBWordConstruct;

class DBTable
{

    /**
     * Temporary field name
     * 
     * @var string
     */
    public $temp_field_name = '__cubef_temp';

    /**
     * Database connection
     *
     * @var DBConnectionConfig
     */
    private DBConnectionConfig $config;

    /**
     * Class constructor
     * 
     * @param string $table_name
     */
    public function __construct(
        public readonly string $name,
        public readonly string|DBConnectionConfig $connection = 'default'
    ) {
        $this->config = is_string($connection)
            ? DBConnection::getConnectionByName($connection)
            : $connection;
    }

    /**
     * Add index to table
     *
     * @param string $index_name
     * @param string $field_name
     * @return PDOStatement
     */
    public function addIndex($index_name, $field_name)
    {
        return $this
            ->getDatabase()
            ->statement(
                DBWordConstruct::alterTableAddIndex(
                    $this->name,
                    $index_name,
                    $field_name
                )
            );
    }

    /**
     * Add new field to table
     * 
     * @param string $structure Structure from \Cube\Modules\DB\DBSchemaBuilder
     * 
     * @return void
     */
    public function addField($structure)
    {
        $this->getDatabase()
            ->statement(
                DBWordConstruct::alterTableAdd(
                    $this->name,
                    $structure
                )
            );
    }

    /**
     * Return average value of specified field
     * 
     * @param string $field Field name
     * 
     * @return int
     */
    public function avg($field)
    {
        return $this->select(["avg($field) as average"])->fetchOne()->average;
    }

    /**
     * Table builder
     *
     * @param callable $callback
     * @return $this
     */
    public function build(callable $callback)
    {
        $this->createTable();
        $callback(new DBTableBuilder($this));
        return new self($this->name);
    }

    /**
     * Return count of rows in table
     * 
     * @return int
     */
    public function count()
    {

        return $this->select(['count(*) as tcount'])->fetchOne()->tcount;
    }

    /**
     * Create new table
     * 
     * @param callable $callback
     * @deprecated 0.0.21 Use build() instead
     * 
     * @return self
     */
    public function create(callable $callback)
    {
        return $this->build($callback);
    }

    /**
     * Delete from table
     * 
     * @return DBDelete
     */
    public function delete()
    {
        return new DBDelete($this);
    }

    /**
     * Describe table
     * 
     * @return array
     */
    public function describe()
    {
        return $this
            ->getDatabase()
            ->statement(DBWordConstruct::describe($this->name))
            ->fetchAll();
    }

    /**
     * Drop table
     * 
     * @return void
     */
    public function drop()
    {
        if (!$this->exists()) {
            return;
        }

        $this
            ->getDatabase()
            ->statement(DBWordConstruct::dropTable($this->name));
    }

    /**
     * Drop foreign key
     *
     * @param string $name
     * @return PDOStatement
     */
    public function dropConstraint(string $name)
    {
        $constraint_name = concat($this->name, '_', $name);
        $db = $this->getDatabase();

        if (!$db->constraintExists($constraint_name)) {
            return;
        }

        return $db->statement(
            DBWordConstruct::dropConstraint(
                $this->name,
                $constraint_name
            )
        );
    }

    /**
     * Drop index
     *
     * @param string $index_name
     * @return PDOStatement
     */
    public function dropIndex($index_name)
    {
        return $this
            ->getDatabase()
            ->statement(DBWordConstruct::dropIndex($this->name, $index_name));
    }

    /**
     * Check if table exists
     * 
     * @return bool
     */
    public function exists()
    {
        return $this->getDatabase()->hasTable($this->name);
    }

    /**
     * Get all fields in the table
     * 
     * @return array
     */
    public function fields()
    {
        $query = $this->getDatabase()->statement('DESCRIBE ' . $this->name);

        if (!$query->rowCount()) {
            return array();
        }

        $fields = array();

        while ($fetch = $query->fetch()) {
            $fields[] = $fetch->Field;
        }

        return $fields;
    }

    /**
     * Check if table has field
     * 
     * @param string $name Field name
     * 
     * @return bool
     */
    public function hasField($name)
    {
        return in_array($name, $this->fields());
    }

    /**
     * Returns table name
     * 
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Insert data into table
     * 
     * @param array $entry Data to enter into table
     * 
     * @return int Insert id
     */
    public function insert(array $entry)
    {
        $insert = new DBInsert($this);
        return $insert->entry($entry);
    }

    /**
     * Replace data into table
     * 
     * @param array $entry Data to enter into table
     * 
     * @return int Insert id
     */
    public function replace(array $entry)
    {
        $insert = new DBReplace($this);
        return $insert->entry($entry);
    }

    /**
     * Remove field from table
     * 
     * @param string $name Field name
     * 
     * @return string[] remaining fields
     */
    public function removeField($name)
    {
        if (!$this->hasField($name)) {
            return $this->fields();
        }

        $this->getDatabase()->statement(
            DBWordConstruct::alterTableRemove(
                $this->name,
                $name
            )
        );

        return $this->fields();
    }

    /**
     * Drop temporary field used in the
     * create table sentence
     */
    public function removeTempField()
    {
        $this->removeField($this->temp_field_name);
    }

    /**
     * Rename table
     * 
     * @param string $new_name New table name
     * 
     * @return string New table name
     */
    public function rename($new_name)
    {
        return $this
            ->getDatabase()
            ->statement(DBWordConstruct::renameTable($this->name, $new_name));
    }

    /**
     * Select from table
     * 
     * @return DBSelect
     */
    public function select(array $fields)
    {
        return new DBSelect($this, $fields);
    }

    /**
     * Raw select statement
     *
     * @param string $statement Sql statement
     * @param string $param Sql parameters
     *
     * @return DBSelect
     */
    public function selectRaw($statement, $params = [])
    {
        $builder = new DBSelect($this);
        return $builder->raw($statement, $params);
    }

    /**
     * Return average value of specified field
     * 
     * @param string $field Field name
     * 
     * @return int
     */
    public function sum($field)
    {
        return $this->select(["sum($field) as totalsum"])->fetchOne()->totalsum;
    }

    /**
     * Truncate table
     * 
     * @return void
     */
    public function truncate()
    {
        $this->getDatabase()->statement(
            DBWordConstruct::truncateTable($this->name)
        );
    }

    /**
     * Update table
     * 
     * @param string[] $entry New update entry
     * 
     * @return DBUpdate
     */
    public function update(array $entry)
    {
        $update = new DBUpdate($this);
        return $update->entry($entry);
    }

    /**
     * Get database
     *
     * @return Database
     */
    public function getDatabase(): Database
    {
        return new Database($this->config);
    }

    /**
     * Create table with temporary field
     * 
     * @return void
     */
    private function createTable()
    {
        if ($this->exists()) return;

        $charset = Env::get('DB_CHARSET');

        #Create temporary structure
        $builder = new DBSchemaBuilder($this, $this->temp_field_name, false);
        $structure = $builder->int()->getStructure();

        #Create table with temporary field
        #Which will be removed immediately
        #The specified fields are added
        $this->getDatabase()->statement(
            DBWordConstruct::createTable(
                $this->name,
                $structure,
                $charset
            )
        );
    }
}
