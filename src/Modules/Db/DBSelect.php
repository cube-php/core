<?php

namespace Cube\Modules\Db;

use Cube\Interfaces\ModelInterface;
use Cube\Misc\ModelCollection;
use Cube\Modules\Db\DBQueryBuilder;

class DBSelect extends DBQueryBuilder
{
    private DBTable $table;

    /**
     * Model class name
     *
     * @var string|null
     */
    public readonly ?ModelInterface $model;

    /**
     * Lock for update
     *
     * @var boolean
     */
    protected bool $lock = false;

    /**
     * Constructor
     * 
     * @param string $table_name
     * @param array $fields
     * @param string|null $model
     */
    public function __construct(DBTable $table, $fields = [], ?ModelInterface $model = null)
    {
        $this->model = $model;

        if ($table) {
            $this->table = $table;
            $this->joinSql('SELECT', implode(', ', $fields), 'FROM', $table->name);
        }
    }

    /**
     * Explain query
     *
     * @return object
     */
    public function explain()
    {
        $this->prependSql('EXPLAIN', null);
        return $this->fetchOne(1);
    }

    /**
     * Fetch content
     * 
     * @param int $offset
     * @param int $limit
     * 
     * @return ModelCollection[]|object[]|null results
     */
    public function fetch($offset, $limit = null)
    {
        #if limit is not passed,
        #the offset argument should be passed as limit
        $offset_id = (int) ($limit ? (int) $offset : 0);
        $limit_id = (int) ($limit ? $limit : $offset);

        $this->joinSql(
            null,
            'LIMIT',
            $this->addParam($offset_id),
            ',',
            $this->addParam($limit_id)
        );

        return $this->get();
    }

    /**
     * Fetch all results
     * 
     * @return ModelCollection|array|null result
     */
    public function fetchAll()
    {
        return $this->get();
    }

    /**
     * Fetch just one result
     * 
     * @return object|null result
     */
    public function fetchOne()
    {
        $fetched = $this->fetch(0, 1);
        return count($fetched) ? $fetched[0] : null;
    }

    /**
     * Return query count
     *
     * @return int
     */
    public function getCount()
    {
        return $this->fetchOne()->count;
    }

    /**
     * Get first row based on specified field
     *
     * @param string $field Database table field
     * @return object|null
     */
    public function getFirst($field)
    {
        $this->orderByRaw("{$field} DESC");
        return $this->fetchOne();
    }

    /**
     * Get last row based on on specified field
     *
     * @param string $field Database table field
     * @return object|null
     */
    public function getLast($field)
    {
        $this->orderByRaw("{$field} ASC");
        return $this->fetchOne();
    }

    /**
     * Group query
     * 
     * @param string $field Field name
     * 
     * @return self
     */
    public function groupBy($field)
    {
        $this->joinSql(null, 'GROUP BY', $field);
        return $this;
    }

    /**
     * Join On query
     * 
     * @param string $column_one
     * @param string $operator
     * @param string $column_two
     * 
     * @return self
     */
    public function crossJoin(callable $fn)
    {
        $fn(new DBJoin($this, 'CROSS JOIN'));
        return $this;
    }

    /**
     * Join On query
     * 
     * @param string $column_one
     * @param string $operator
     * @param string $column_two
     * 
     * @return self
     */
    public function join(callable $fn)
    {
        $fn(new DBJoin($this));
        return $this;
    }

    /**
     * Inner Join On query
     * 
     * @param string $column_one
     * @param string $operator
     * @param string $column_two
     * 
     * @return self
     */
    public function innerJoin(callable $fn)
    {
        $fn(new DBJoin($this, 'INNER JOIN'));
        return $this;
    }

    /**
     * Left Join On query
     * 
     * @param string $column_one
     * @param string $operator
     * @param string $column_two
     * 
     * @return self
     */
    public function leftJoin(callable $fn)
    {
        $fn(new DBJoin($this, 'LEFT JOIN'));
        return $this;
    }

    /**
     * Right Join On query
     * 
     * @param string $column_one
     * @param string $operator
     * @param string $column_two
     * 
     * @return self
     */
    public function rightJoin(callable $fn)
    {
        $fn(new DBJoin($this, 'RIGHT JOIN'));
        return $this;
    }

    /**
     * Lock for update
     *
     * @return $this
     */
    public function lock()
    {
        $this->lock = true;
        return $this;
    }

    /**
     * Order query
     * 
     * @param array $order
     * 
     * @return self
     */
    public function orderBy($orders)
    {

        if (!$orders) {
            return $this;
        }

        $orders_list = [];

        foreach ($orders as $field => $method) {
            $orders_list[] = $field . ' ' . $method;
        }

        $this->joinSql(null, 'ORDER BY', implode(', ', $orders_list));
        return $this;
    }

    /**
     * Ascending order
     *
     * @param string $field
     * @return $this
     */
    public function orderByAsc(string $field)
    {
        return $this->orderBy([$field => 'ASC']);
    }

    /**
     * Descending order
     *
     * @param string $field
     * @return $this
     */
    public function orderByDesc(string $field)
    {
        return $this->orderBy([$field => 'DESC']);
    }

    /**
     * Raw order by query
     * 
     * @param string    $statement Query statement
     * @param string[]  $params
     */
    public function orderByRaw($statement, $params = [])
    {
        $this->joinSql(null, 'ORDER BY', $statement);
        $this->bindParam($params);

        return $this;
    }

    /**
     * Randomize results
     * 
     * @return self
     */
    public function randomize(int $range = 0)
    {
        if ($range) {
            $this->sql_query = 'SELECT * FROM (' . $this->getSqlQuery() . ' LIMIT ' . $range . ') AS subquery';
        }

        return $this->orderByRaw('RAND()');
    }

    /**
     * Union stateent
     * 
     * @param DBQueryBuilder $query Query
     * 
     * @return self
     */
    public function union(DBQueryBuilder $query)
    {
        $queryToAppend = (string) $query;
        $this->joinSql(null, 'UNION', $queryToAppend);

        return $this;
    }

    /**
     * UnionAll statement
     * 
     * @param DBQueryBuilder $query
     * 
     * @return self
     */
    public function unionAll(DBQueryBuilder $query)
    {

        $queryToAppend = (string) $query;
        $this->joinSql(null, 'UNION ALL', $queryToAppend);

        return $this;
    }

    /**
     * Set sql query
     *
     * @param string[] ...$args
     * @return self
     */
    public function joinSql(...$args)
    {
        $new_args = every($args, function ($arg) {
            return str_replace('$.', $this->table->name . '.', (string) $arg);
        });

        parent::joinSql(...$new_args);
        return $this;
    }

    /**
     * Finish query
     * 
     * @return object|null
     */
    private function get()
    {
        $this->wrapModel();

        if ($this->lock) {
            $this->joinSql(null, 'FOR UPDATE');
        }

        $sql = $this->getSqlQuery();
        $params = $this->getSqlParameters();

        $stmt = $this->table->connection->query($sql, $params);
        $wrapper = $this->bundle;

        if (!$stmt->rowCount()) {
            return $wrapper ? ModelCollection::new([]) : [];
        }

        $fetched_data = $stmt->fetchAll();

        if ($wrapper && is_array($fetched_data)) {

            $content = array_map(function ($item) use ($wrapper) {
                return $wrapper::wrapData($item);
            }, $fetched_data);

            return new ModelCollection($content);
        }

        return $fetched_data;
    }

    /**
     * Class name to wrap retrieved item
     *
     * @return self
     */
    private function wrapModel()
    {
        $model = $this->model;

        if (!$model) {
            return;
        }

        $this->bundle = $model;
        return $this;
    }
}
