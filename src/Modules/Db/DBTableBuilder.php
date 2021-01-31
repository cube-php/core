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

    /**
     * DBTableBuilder constructor
     * 
     * @param DBTable $table
     */
    public function __construct(DBTable $table)
    {
        $this->table = $table;
    }

    /**
     * Add field to table
     * 
     * @return DBSchemaBuilder
     */
    public function field($name)
    {
        return new DBSchemaBuilder($this->table, $name);
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
}