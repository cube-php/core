<?php

namespace Cube\Modules\Db;

/**
 * Share callback-based joins across SELECT, UPDATE, and DELETE builders.
 */
trait DBJoins
{
    /**
     * Require the SQL writer supplied by the consuming query builder.
     *
     * @param mixed ...$args SQL fragments to concatenate.
     * @return $this
     */
    abstract public function joinSql(...$args);

    /**
     * Add a cross join; the callback may omit an ON condition.
     * 
     * @param callable(DBJoin): mixed $fn Callback that configures the join.
     * @return $this
     */
    public function crossJoin(callable $fn)
    {
        return $this->addJoin($fn, 'CROSS JOIN');
    }

    /**
     * Add a join using the table and condition supplied by the callback.
     * 
     * @param callable(DBJoin): mixed $fn Callback that configures the join.
     * @return $this
     */
    public function join(callable $fn)
    {
        return $this->addJoin($fn, 'JOIN');
    }

    /**
     * Add an inner join using the supplied callback.
     * 
     * @param callable(DBJoin): mixed $fn Callback that configures the join.
     * @return $this
     */
    public function innerJoin(callable $fn)
    {
        return $this->addJoin($fn, 'INNER JOIN');
    }

    /**
     * Add a left join using the supplied callback.
     * 
     * @param callable(DBJoin): mixed $fn Callback that configures the join.
     * @return $this
     */
    public function leftJoin(callable $fn)
    {
        return $this->addJoin($fn, 'LEFT JOIN');
    }

    /**
     * Add a right join using the supplied callback.
     * 
     * @param callable(DBJoin): mixed $fn Callback that configures the join.
     * @return $this
     */
    public function rightJoin(callable $fn)
    {
        return $this->addJoin($fn, 'RIGHT JOIN');
    }

    /**
     * Build a join fragment before passing it to the statement-specific writer.
     *
     * The temporary builder keeps callback output separate from existing clauses,
     * allowing write queries to place joins before SET or WHERE.
     *
     * @param callable(DBJoin): mixed $fn Callback that configures the join.
     * @param string $type SQL join keyword, such as INNER JOIN.
     * @return $this
     */
    protected function addJoin(callable $fn, string $type)
    {
        $builder = new DBQueryBuilder();
        $fn(new DBJoin($builder, $type));
        $sql = (string) $builder;

        if ($sql !== '') {
            $this->appendJoinSql($sql);
        }

        return $this;
    }

    /**
     * Append a join at the current position, as used by SELECT queries.
     *
     * Write builders override this hook to defer placement of the fragment.
     *
     * @param string $sql Join fragment including its leading separator.
     * @return void
     */
    protected function appendJoinSql(string $sql): void
    {
        $this->joinSql($sql);
    }
}
