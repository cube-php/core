<?php

namespace Cube\Modules\Db;

/**
 * Render write joins before SET/WHERE, regardless of when joins are added.
 *
 * Consuming builders provide the base table and writeQueryHeader(). DELETE also
 * overrides joinedQueryHeader() to identify the table whose rows are removed.
 */
trait DBWriteJoins
{
    use DBJoins;

    /**
     * Join fragments in callback order, each retaining its leading separator.
     *
     * @var string
     */
    private string $join_sql = '';

    /**
     * Store a join and resolve the $. shorthand to the base table name.
     *
     * @param string $sql Join fragment including its leading separator.
     * @return void
     */
    protected function appendJoinSql(string $sql): void
    {
        $this->join_sql .= str_replace('$.', $this->table->name . '.', $sql);
    }

    /**
     * Insert joins after the statement header without mutating the stored query.
     *
     * Keeping rendering separate preserves SET/WHERE parameter order and prevents
     * duplicate joins when the query is rendered more than once.
     *
     * @return string SQL ready for execution.
     */
    protected function getSqlQuery()
    {
        $sql = parent::getSqlQuery();
        if ($this->join_sql === '') {
            return $sql;
        }

        $header = $this->writeQueryHeader();
        // Locate the header after any prefix added through prependSql().
        $position = strpos($sql, $header);

        return substr_replace(
            $sql,
            $this->joinedQueryHeader() . $this->join_sql,
            $position,
            strlen($header)
        );
    }

    /**
     * Return the statement header used when joins are present.
     *
     * UPDATE retains its original header; DELETE supplies an explicit target.
     *
     * @return string
     */
    protected function joinedQueryHeader(): string
    {
        return $this->writeQueryHeader();
    }
}
