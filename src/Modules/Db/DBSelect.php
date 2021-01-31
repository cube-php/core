<?php

namespace Cube\Modules\Db;

use Cube\Exceptions\ModelException;
use Cube\Interfaces\ModelInterface;
use InvalidArgumentException;

use Cube\Modules\DB;
use Cube\Modules\Db\DBQueryBuilder;
use ReflectionClass;

class DBSelect extends DBQueryBuilder
{   
    /**
     * Model class name
     *
     * @var string|null
     */
    public $model = null;

    /**
     * Constructor
     * 
     * @param string $table_name
     * @param array $fields
     * @param string|null $model
     */
    public function __construct($table_name = '', $fields = [], ?string $model = null)
    {
        $this->model = $model;

        if($table_name) {
            $this->joinSql('SELECT', implode(', ', $fields), 'FROM', $table_name);
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
     * @return object[]|null results
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
            $this->addParam($offset_id), ',',
            $this->addParam($limit_id)
        );

        return $this->get();
    }

    /**
     * Fetch all results
     * 
     * @return object[]|null result
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
        return $fetched ? $fetched[0] : null;
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
        $this->joinSql('GROUP BY', $field);
        return $this;
    }

    /**
     * Join On query
     * 
     * @param string $field Field name
     * @param string $column_one
     * @param string $operator
     * @param string $column_two
     * 
     * @return self
     */
    public function join($field, $column_one, $operator, $column_two)
    {
        $this->joinSql(null, 'JOIN', $column_one, $operator, $column_two);
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

        if(!$orders) {
            return $this;
        }

        $orders_list = [];

        foreach($orders as $field => $method) {
            $orders_list[] = $field . ' ' . $method;
        }
        
        $this->joinSql(null, 'ORDER BY', implode(', ', $orders_list));
        return $this;
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
    public function randomize()
    {
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
     * Finish query
     * 
     * @return object|null
     */
    private function get()
    {
        $this->wrapModel();
        $sql = $this->getSqlQuery();
        $params = $this->getSqlParameters();

        $stmt = DB::statement($sql, $params);
        
        if(!$stmt->rowCount()) {
            return [];
        }

        $fetched_data = $stmt->fetchAll();
        $wrapper = $this->bundle;

        if($wrapper && is_array($fetched_data)) {

            return array_map(function ($item) use ($wrapper) {
                return new $wrapper($item);
            }, $fetched_data);
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
        $class_name = $this->model;
        
        if(!$class_name) {
            return;
        }

        if(!class_exists($class_name)) {
            throw new InvalidArgumentException
                ('Cannot use undefined class "' . $class_name . '" ');
        }

        $reflector = new ReflectionClass($this->model);

        if(!in_array(ModelInterface::class, $reflector->getInterfaceNames())) {
            throw new ModelException('Invalid model');
        }

        $this->bundle = $class_name;
        return $this;
    }
}