<?php

namespace Cube\Modules\Db;

use Cube\Modules\DB;
use Cube\Modules\Db\DBQueryBuilder;

class DBUpdate extends DBQueryBuilder
{
    /**
     * Constructor
     * 
     * @param string $table_name
     */
    public function __construct(public readonly DBTable $table)
    {
        $this->joinSql('UPDATE', $table->name);
    }

    /**
     * Create insert entry
     * 
     * @param string[] $params
     * 
     * @return int
     */
    public function entry($params)
    {
        $params['updated_at'] = getnow();
        $this->make($params);
        return $this;
    }

    /**
     * Query executor
     * 
     * @return int
     */
    public function fulfil()
    {
        $db = $this
            ->table
            ->getDatabase()
            ->statement($this->getSqlQuery(), $this->getSqlParameters());

        return $db->rowCount();
    }

    /**
     * Make query
     * 
     * @param string[] $params Parameters to make query from
     * 
     * @return 
     */
    private function make($params)
    {
        $keys = array_keys($params);
        $values = array_values($params);
        $placeholders = [];

        foreach ($keys as $key) $placeholders[] = "{$key} = ?";

        $fields = implode(',', $placeholders);
        $this->bindParam($values);
        $this->joinSql(null, 'SET', $fields);
    }
}
