<?php

namespace Cube\Modules\Db;

class DBJoin
{
    /**
     * Constructor
     *
     * @param string $table_name
     */
    public function __construct(protected DBQueryBuilder $builder, protected string $type = 'JOIN') {}

    /**
     * Set join table name
     *
     * @param string $type
     * @return self
     */
    public function table(string $table_name): self
    {
        $this->builder->joinSql(null, $this->type, $table_name);
        return $this;
    }

    /**
     * Set join condition
     *
     * @param string ...$condition
     * @return self
     */
    public function on(...$condition): self
    {
        $this->builder->joinSql(null, 'ON', ...$condition);
        return $this;
    }
}
