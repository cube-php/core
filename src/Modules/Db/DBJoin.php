<?php

namespace Cube\Modules\Db;

class DBJoin
{
    /**
     * Configure a join fragment on the supplied query builder.
     *
     * @param DBQueryBuilder $builder Builder receiving the SQL fragments.
     * @param string $type SQL join keyword.
     */
    public function __construct(protected DBQueryBuilder $builder, protected string $type = 'JOIN') {}

    /**
     * Append the joined table or table expression.
     *
     * @param string $table_name Table name, optionally including an alias.
     * @return self
     */
    public function table(string $table_name): self
    {
        $this->builder->joinSql(null, $this->type, $table_name);
        return $this;
    }

    /**
     * Append a raw ON condition.
     *
     * Fragments are joined with spaces and are not parameter-bound or escaped.
     *
     * @param string ...$condition Trusted SQL fragments, such as two columns and an operator.
     * @return self
     */
    public function on(...$condition): self
    {
        $this->builder->joinSql(null, 'ON', ...$condition);
        return $this;
    }
}
