<?php

namespace Cube\Modules\Db;

use InvalidArgumentException;

use Cube\Modules\DB;

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
     * Table name
     * 
     * @var string
     */
    private $name;

    /**
     * Class constructor
     * 
     * @param string $table_name
     */
    public function __construct($table_name)
    {
        $this->name = $table_name;
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
        return DB::statement(DBWordConstruct::alterTableAddIndex($this->name, $index_name, $field_name));
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
        DB::statement(
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
     * Return count of rows in table
     * 
     * @return int
     */
    public function count() {
        
        return $this->select(['count(*) as tcount'])->fetchOne()->tcount;
    }

    /**
     * Create new table
     * 
     * @param callable $callback
     * 
     * @return self
     */
    public function create(callable $callback)
    {

        $this->createTemp();

        #Do call back
        $callback(new DBTableBuilder($this));
        return new self($this->name);
    }

    /**
     * Delete from table
     * 
     * @return DBDelete
     */
    public function delete()
    {
        return new DBDelete($this->name);
    }

    /**
     * Describe table
     * 
     * @return array
     */
    public function describe()
    {
        $stmt = DB::statement(DBWordConstruct::describe($this->name));
        return $stmt->fetchAll();
    }

    /**
     * Drop table
     * 
     * @return void
     */
    public function drop()
    {
        if(!$this->exists()) {
            return;
        }

        DB::statement(DBWordConstruct::dropTable($this->name));
    }

    /**
     * Drop index
     *
     * @param string $index_name
     * @return PDOStatement
     */
    public function dropIndex($index_name)
    {
        return DB::statement(DBWordConstruct::dropIndex($this->name, $index_name));
    }

    /**
     * Check if table exists
     * 
     * @return bool
     */
    public function exists()
    {
        return DB::hasTable($this->name);
    }

    /**
     * Get all fields in the table
     * 
     * @return array
     */
    public function fields()
    {
        $query = DB::statement('DESCRIBE ' . $this->name);
        
        if(!$query->rowCount()) return array();

        $fields = array();

        while($fetch = $query->fetch()) {
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
        $insert = new DBInsert($this->name);
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
        $insert = new DBReplace($this->name);
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
        DB::statement(
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
        return DB::statement(DBWordConstruct::renameTable($this->name, $new_name));
    }

    /**
     * Select from table
     * 
     * @return DBSelect
     */
    public function select(array $fields)
    {
        return new DBSelect($this->name, $fields);
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
        $builder = new DBSelect;
        return $builder->raw($this->name, $statement, $params);
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
        DB::statement(
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
        $update = new DBUpdate($this->name);
        return $update->entry($entry);
    }

    /**
     * Create table with temporary field
     * 
     * @return void
     */
    private function createTemp()
    {
        if($this->exists()) return;

        #Create temporary structure
        $builder = new DBSchemaBuilder($this, $this->temp_field_name, false);
        $structure = $builder->int()->getStructure();

        #Create table with temporary field
        #Which will be removed immediately
        #The specified fields are added
        DB::statement(
            DBWordConstruct::createTable(
                $this->name,
                $structure
            )
        );
    }
}