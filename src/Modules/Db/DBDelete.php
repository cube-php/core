<?php

namespace Cube\Modules\Db;

use Cube\Modules\DB;
use Cube\Modules\Db\DbQueryBuilder;

class DBDelete extends DBQueryBuilder
{

    /**
     * Class constructor
     * 
     * @param string $table_name
     */
    public function __construct($table_name)
    {
        $this->joinSql('DELETE FROM', $table_name);
    }

    /**
     * Fulfil query
     * 
     * @return int deleted rows
     */
    public function fulfil()
    {
        $query = DB::statement($this->getSqlQuery(), $this->getSqlParameters());
        return $query->rowCount();
    }
}