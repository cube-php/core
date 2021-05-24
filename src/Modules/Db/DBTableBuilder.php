<?php

namespace Cube\Modules\Db;

use Cube\Modules\Db\DBTable;
use Cube\Modules\Db\DBSchemaBuilder;

class DBTableBuilder
{
    /**
     * Table name
     * 
     * @var DBTable
     */
    private $table;

    private ?int $query_position = null;

    private ?string $query_position_row_name = null;

    /**
     * DBTableBuilder constructor
     * 
     * @param DBTable $table
     */
    public function __construct(DBTable $table, ?int $query_position = null, ?string $query_position_row_name = null)
    {
        $this->table = $table;
        $this->query_position = $query_position;
        $this->query_position_row_name = $query_position_row_name;
    }

    /**
     * Add field to table
     * 
     * @return DBSchemaBuilder
     */
    public function field($name)
    {
        $builder = new DBSchemaBuilder($this->table, $name);
        $builder->setPosition($this->query_position, $this->query_position_row_name);

        return $builder;
    }

    /**
     * Add field to table after row
     *
     * @param string $row_name
     * @param callable $callback
     * @return void
     */
    public function after(string $row_name, callable $callback)
    {
        $this->setQueryPosition(DBSchemaBuilder::POSITION_AFTER, $row_name);
        $callback($this);
        $this->setQueryPosition();
    }

    /**
     * Add field to the beginning of the table
     *
     * @param callable $callback
     * @return void
     */
    public function first(callable $callback)
    {
        $this->setQueryPosition(DBSchemaBuilder::POSITION_BEFORE);
        $callback($this);
        $this->setQueryPosition();
    }

    /**
     * Remove field
     *
     * @param string $row_name
     * @return void
     */
    public function remove(string $row_name)
    {
        $this->table->removeField($row_name);
    }

    /**
     * Assign a foreign key
     *
     * @param string $field_name
     * @return DBTableForeignKey
     */
    public function foreignKey(string $field_name): DBTableForeignKey
    {
        return new DBTableForeignKey($this->table, $field_name);
    }

    /**
     * Get table builder is building for
     *
     * @return DBTable
     */
    public function getTable(): DBTable
    {
        return $this->table;
    }

    /**
     * Moved the creation of extra fields to end of action
     * 
     * @return void
     */
    public function __destruct()
    {
        $this->field('created_at')->datetime();
        $this->field('updated_at')->datetime()->nullable();
    }

    private function setQueryPosition(?int $position = null, ?string $row_name = null)
    {
        $this->query_position = $position;
        $this->query_position_row_name = $row_name;
    }
}