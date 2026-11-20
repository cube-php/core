<?php

namespace Cube\Modules\Db;

use Cube\Exceptions\ModelException;
use Cube\Http\Model;
use Cube\Interfaces\ModelInterface;
use Cube\Misc\ModelCollection;
use Cube\Modules\Db\DBQueryBuilder;

class DBSelect extends DBQueryBuilder
{
    use DBJoins;

    private const JOINED_PARENT_KEY = '__cube_relation_parent_key';

    private DBTable $table;

    /**
     * Normalized joined relations, including SQL hydration metadata.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $joined_relations = [];

    /**
     * Whether joined rows must be grouped into a to-many relation.
     */
    private bool $has_joined_many = false;

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
     * Skip locked rows
     *
     * @var boolean
     */
    protected bool $skip_locked = false;

    /**
     * Constructor
     * 
     * @param DBTable $table
     * @param array $fields
     * @param string|null $model
     */
    public function __construct(DBTable $table, array $fields = [], ?ModelInterface $model = null)
    {
        $this->table = $table;
        $this->model = $model;

        if ($model instanceof Model) {
            $fields = $this->prepareJoinedRelations($fields, $model);
        }

        $this->joinSql('SELECT', implode(', ', $fields), 'FROM', $table->name);
        $this->joinRelations();
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
     * @return ModelCollection|object[]|null results
     */
    public function fetch($offset, $limit = null)
    {
        #if limit is not passed,
        #the offset argument should be passed as limit
        $offset_id = (int) ($limit ? (int) $offset : 0);
        $limit_id = (int) ($limit ? $limit : $offset);

        // Applying LIMIT directly to a to-many join truncates child rows and
        // counts joined rows instead of parent models. Group first, then slice.
        if ($this->has_joined_many) {
            $results = $this->get();
            $items = $results instanceof ModelCollection
                ? $results->getArrayCopy()
                : $results;

            return ModelCollection::new(array_slice($items, $offset_id, $limit_id));
        }

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
        $field = $this->fieldName($field);
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
        $field = $this->fieldName($field);
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
        $this->joinSql(null, 'GROUP BY', $this->fieldName($field));
        return $this;
    }

    /**
     * Lock for update
     *
     * @param bool $skip_locked
     * @return $this
     */
    public function lock(bool $skip_locked = false)
    {
        $this->lock = true;
        $this->skip_locked = $skip_locked;
        return $this;
    }

    /**
     * Order query
     * 
     * @param array $orders
     * 
     * @return self
     */
    public function orderBy(array $orders): self
    {

        if (!$orders) {
            return $this;
        }

        $orders_list = [];

        foreach ($orders as $field => $method) {
            $orders_list[] = $this->fieldName($field) . ' ' . $method;
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

        if ($this->skip_locked) {
            $this->joinSql(null, 'SKIP LOCKED');
        }

        $sql = $this->getSqlQuery();
        $params = $this->getSqlParameters();

        $stmt = $this->table->connection->query($sql, $params);
        $wrapper = $this->bundle;

        $fetched_data = $stmt->fetchAll();

        if (!count($fetched_data)) {
            return $wrapper ? ModelCollection::new([]) : [];
        }

        if ($wrapper && is_array($fetched_data)) {
            return count($this->joined_relations)
                ? $this->hydrateJoinedModels($fetched_data, $wrapper)
                : new ModelCollection(array_map(function ($item) use ($wrapper) {
                    return $wrapper::wrapData($item);
                }, $fetched_data));
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

    /**
     * Add joined select fields and retain the metadata needed for hydration.
     *
     * @param string[] $fields
     * @param Model $model
     * @return string[]
     */
    private function prepareJoinedRelations(array $fields, Model $model): array
    {
        $definitions = $model::getRelationDefinitions();

        if (!count($definitions)) {
            return $fields;
        }

        $fields = array_map(
            fn($field) => $field === '*' ? $this->table->name . '.*' : $field,
            $fields
        );

        $parent_key = $this->unqualifiedField(
            $model::getDatabaseField($model::getPrimaryKey())
        );
        $fields[] = concat(
            $this->table->name,
            '.',
            $parent_key,
            ' AS ',
            self::JOINED_PARENT_KEY
        );

        foreach ($definitions as $relation_name => $definition) {
            /** @var class-string<Model> $related_model */
            $related_model = $definition['model'];
            $related_table = $related_model::query();

            if ($related_table->connection->getConnection() !== $this->table->connection->getConnection()) {
                throw new ModelException(concat(
                    'Relation "',
                    $relation_name,
                    '" cannot join models from different database connections'
                ));
            }

            $index = count($this->joined_relations);
            $table_alias = concat('__cube_relation_', $index);
            $column_prefix = concat('__cube_relation_', $index, '__');
            $definition['table_name'] = $related_table->name;
            $definition['table_alias'] = $table_alias;
            $definition['column_prefix'] = $column_prefix;

            $fields = array_merge(
                $fields,
                $related_model::getJoinedSelectFields($table_alias, $column_prefix)
            );
            $this->joined_relations[$relation_name] = $definition;
            $this->has_joined_many = $this->has_joined_many || $definition['type'] === 'many';
        }

        return $fields;
    }

    /**
     * Append LEFT JOIN clauses for every declared relation.
     */
    private function joinRelations(): void
    {
        if (!$this->model instanceof Model) {
            return;
        }

        foreach ($this->joined_relations as $definition) {
            /** @var class-string<Model> $related_model */
            $related_model = $definition['model'];
            $base_field = $this->unqualifiedField(
                $this->model::getDatabaseField($definition['local_key'])
            );
            $related_field = $this->unqualifiedField(
                $related_model::getDatabaseField($definition['foreign_key'])
            );
            $table_expression = concat(
                $definition['table_name'],
                ' AS ',
                $definition['table_alias']
            );

            $this->leftJoin(function (DBJoin $join) use ($definition, $base_field, $related_field, $table_expression) {
                $join->table($table_expression)->on(
                    $this->table->name . '.' . $base_field,
                    '=',
                    $definition['table_alias'] . '.' . $related_field
                );
            });
        }
    }

    /**
     * Collapse joined rows into parent models and their loaded relations.
     *
     * @param object[] $rows
     * @param ModelInterface $wrapper
     * @return ModelCollection
     */
    private function hydrateJoinedModels(array $rows, ModelInterface $wrapper): ModelCollection
    {
        $groups = [];
        $order = [];

        foreach ($rows as $row_index => $row) {
            $parent_data = (array) $row;
            $parent_key = $parent_data[self::JOINED_PARENT_KEY] ?? $row_index;
            unset($parent_data[self::JOINED_PARENT_KEY]);
            $group_key = serialize($parent_key);
            $related_payloads = [];

            foreach ($this->joined_relations as $relation_name => $definition) {
                $payload = [];
                $prefix = $definition['column_prefix'];

                foreach (array_keys($parent_data) as $field) {
                    if (!str_starts_with($field, $prefix)) {
                        continue;
                    }

                    $payload[substr($field, strlen($prefix))] = $parent_data[$field];
                    unset($parent_data[$field]);
                }

                $related_payloads[$relation_name] = $payload;
            }

            if (!isset($groups[$group_key])) {
                $relations = [];

                foreach ($this->joined_relations as $relation_name => $definition) {
                    $relations[$relation_name] = $definition['type'] === 'many'
                        ? ModelCollection::new([])
                        : null;
                }

                $groups[$group_key] = [
                    'model' => $wrapper::wrapData((object) $parent_data),
                    'relations' => $relations,
                    'seen' => [],
                ];
                $order[] = $group_key;
            }

            foreach ($this->joined_relations as $relation_name => $definition) {
                /** @var class-string<Model> $related_model */
                $related_model = $definition['model'];
                $payload = $related_payloads[$relation_name];
                $primary_key = $related_model::getPrimaryKey();

                if (!array_key_exists($primary_key, $payload) || $payload[$primary_key] === null) {
                    continue;
                }

                $identity = serialize($payload[$primary_key]);
                if (isset($groups[$group_key]['seen'][$relation_name][$identity])) {
                    continue;
                }

                $related = $related_model::wrapData((object) $payload);
                $groups[$group_key]['seen'][$relation_name][$identity] = true;

                if ($definition['type'] === 'many') {
                    $groups[$group_key]['relations'][$relation_name][] = $related;
                    continue;
                }

                $groups[$group_key]['relations'][$relation_name] ??= $related;
            }
        }

        $models = [];
        foreach ($order as $group_key) {
            /** @var Model $model */
            $model = $groups[$group_key]['model'];

            foreach ($this->joined_relations as $relation_name => $definition) {
                $model->setLoadedRelation(
                    $relation_name,
                    $groups[$group_key]['relations'][$relation_name],
                    $definition
                );
            }

            $models[] = $model;
        }

        return ModelCollection::new($models);
    }

    /**
     * Remove a table qualifier from a model or database field.
     */
    private function unqualifiedField(string $field): string
    {
        $position = strrpos($field, '.');
        return $position === false ? $field : substr($field, $position + 1);
    }

    /**
     * Normalize model-facing field names before adding them to SQL.
     *
     * @param string $field Field name
     * @return string
     */
    protected function fieldName(string $field): string
    {
        $model = $this->model;

        if (!$model instanceof Model) {
            return $field;
        }

        $database_field = $model::getDatabaseField($field);

        if (count($this->joined_relations)
            && !str_contains($database_field, '.')
            && !preg_match('/\s|\(/', $database_field)) {
            return $this->table->name . '.' . $database_field;
        }

        return $database_field;
    }
}
