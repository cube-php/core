<?php

namespace Cube\Http;

use Cube\Exceptions\ModelException;
use Cube\Modules\DB;
use Cube\Modules\Db\DBTable;
use Cube\Modules\Db\DBSelect;
use Cube\Interfaces\ModelInterface;
use Cube\Misc\ModelCollection;
use Cube\Modules\Db\DBUpdate;
use Cube\Modules\Db\DBDelete;
use Cube\Traits\Onceable;
use InvalidArgumentException;
use ReflectionClass;

class Model implements ModelInterface
{
    use Onceable;

    protected const CAST_TYPE_INT = 1;
    protected const CAST_TYPE_STRING = 2;
    protected const CAST_TYPE_FLOAT = 3;
    protected const CAST_TYPE_BOOLEAN = 4;

    /**
     * Model database table name
     * 
     * @var string
     */
    protected static $schema;

    /**
     * Selectable fields from specified $schema
     * 
     * @var array
     */
    protected static $fields = array();

    /**
     * Private select fields
     *
     * @var array
     */
    protected static $private_fields = array();

    /**
     * Primary key field name
     * 
     * @var string
     */
    protected static $primary_key = 'id';

    /**
     * Property type cast
     * 
     * @var array
     */
    protected array $cast = array();

    /**
     * Auto save model components
     * 
     * Calling the save method on a model will not be required if this is true
     * [Not recommended]
     * 
     * @var bool
     */
    protected bool $autosave = false;

    /**
     * Methods to return with data
     * 
     * @var array
     */
    protected array $with_data = array();

    /**
     * Properties to not return with data
     * 
     * @var array
     */
    protected array $without_data = array();

    /**
     * Only list or properties to return with data
     *
     * @var array
     */
    protected array $only_data = array();

    /**
     * Order returned data
     *
     * @var array
     */
    protected array $data_order = array();

    /**
     * Model data
     *
     * @var array
     */
    private array $_data = array();

    /**
     * Private model data
     * 
     * @var array
     */
    private array $_data_private = array();

    /**
     * Model updates
     *
     * @var array
     */
    private $_updates = array();

    /**
     * Relations
     *
     * @var array
     */
    private $_relation = array();

    /**
     * Relations[]
     *
     * @var array
     */
    private $_relations = array();

    /**
     * Check if is new data
     *
     * @var boolean
     */
    private $_is_new = true;

    /**
     * Getter
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (in_array($name, array_keys($this->_data))) {
            return $this->_data[$name];
        }

        if (in_array($name, array_keys($this->_data_private))) {
            return $this->_data_private[$name];
        }

        if (method_exists($this, $name)) {
            return $this->$name();
        }

        return null;
    }

    /**
     * Check if property is set
     *
     * @param string $name
     * @return boolean
     */
    public function __isset($name): bool
    {
        $keys = array_keys($this->_data);
        return in_array($name, $keys) || method_exists($this, $name);
    }

    /**
     * Add an update
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $has_key = $this->_data && in_array(self::$primary_key, $this->_data);

        if (!$has_key) {
            $this->_data[$name] = $value;
        }

        $this->_updates[$name] = $value;

        if (!$this->autosave) {
            return;
        }

        $this->save();
    }

    /**
     * Save updates
     *
     * @return int|bool
     */
    public function save(): bool
    {
        $key = self::getPrimaryKey();

        if ($this->_is_new) {
            $this->_data = array_merge($this->_data, $this->_updates);
            $id = static::createEntry($this->_data);
            $this->_data[$key] = $id;
            $this->_updates = [];
            $this->_is_new = false;
            return true;
        }

        $entry_id = $this->{$key};
        $saved = !!static::update($this->_updates)
            ->where($key, $entry_id)
            ->fulfil();


        if ($saved) {
            $old_data = (array) $this->_data;
            array_walk($this->_updates, function ($value, $field) use (&$old_data) {
                $old_data[$field] = $value;
            });

            $this->_data = $old_data;
            static::onUpdate($this);
        }

        $this->_updates = [];
        return $saved;
    }

    /**
     * Relation
     *
     * @param string $model
     * @param string $field
     * @param string|null $name
     * @return ModelInterface|null
     */
    public function relation(string $model, string $field, ?string $name = null)
    {
        $cname = concat('relation$$', $model, '$$', $field, '$$', $name);
        return $this->once(function () use ($model, $name, $field) {

            $field_name = $name ?: self::$primary_key;
            $class = new ReflectionClass($model);

            if (!$class->implementsInterface(ModelInterface::class)) {
                throw new InvalidArgumentException('Invalid model class');
            }

            return $name
                ? $model::findBy($field_name, $this->{$field})
                : $model::find($this->{$field});
        }, $cname);
    }

    /**
     * Relations
     *
     * @param string $model
     * @param string $field
     * @param string|null $name
     * @return array
     */
    public function relations(string $model, string $field, ?string $name = null)
    {
        $cname = concat('relations$$', $model, '$$', $field, '$$', $name);
        return $this->once(function () use ($name, $model, $field) {

            $class = new ReflectionClass($model);

            if (!$class->implementsInterface(ModelInterface::class)) {
                throw new InvalidArgumentException('Invalid model class');
            }

            $field_name = $name ?: self::$primary_key;
            $result = $model::findAllBy($field_name, $this->{$field});
            return $result;
        }, $cname);
    }

    /**
     * Model content
     *
     * @return array
     */
    public function data(): array
    {
        return $this->once(function () {
            $data = $this->_data;
            $data_keys = array_keys($data);
            $only_data = $this->only_data;

            if ($only_data) {
                $return_data = array();
                every($only_data, function ($key) use ($data, &$return_data) {
                    $return_data[$key] = $data[$key];
                });

                $data = $return_data;
            }

            every($this->without_data, function ($val) use (&$data, $data_keys) {
                if (!in_array($val, $data_keys)) {
                    throw new ModelException(
                        concat('Property"', $val, '" is undefined for ', get_called_class())
                    );
                }
                unset($data[$val]);
            });

            every($this->with_data, function ($val, $index) use (&$data, $data_keys) {

                $cls = get_called_class();
                $key = !is_numeric($index) ? $index : $val;

                if (is_callable($val)) {
                    return $data[$key] = $val();
                }

                if (str_starts_with($val, '@')) {
                    $property_name = substr($val, 1);

                    if (!in_array($property_name, $data_keys)) {
                        throw new ModelException(
                            concat('Property "', $property_name, '" not defined in "', $cls, '"')
                        );
                    }

                    return $data[$key] = $this->_data[$property_name];
                }

                $values = explode('.', $val);
                $method_name = $values[0];

                if (!method_exists($cls, $method_name)) {
                    throw new ModelException(
                        concat('Property "', $val, '" not defined in "', $cls, '"')
                    );
                }

                $value_fn = function () use ($method_name, $values) {
                    $value = $this->{$method_name}();

                    if (count($values) === 1) {
                        return $value;
                    }

                    $nested_value = $value;
                    $iterable_values = array_slice($values, 1);

                    every($iterable_values, function ($value) use (&$nested_value) {
                        $data = (array) $nested_value;
                        $nested_value = $data[$value];
                    });

                    return $nested_value;
                };

                $value = $value_fn();
                if (is_object($value)) {

                    $ref_class = new ReflectionClass($value);

                    if ($ref_class->implementsInterface(ModelInterface::class)) {
                        return $data[$key] = $value->data();
                    }
                }

                return $data[$key] = $value;
            });

            $order = $this->data_order;

            if (!count($order)) {
                return $data;
            }

            $ordered_data = array();
            every($order, function ($name) use (&$data, &$ordered_data) {
                $ordered_data[$name] = $data[$name];
                unset($data[$name]);
            });

            $new_data = array_merge($ordered_data, $data);
            return $new_data;
        });
    }

    /**
     * Check if current instance is same as specified instance
     *
     * @param ModelInterface $instance
     * @return boolean
     */
    public function is(ModelInterface $instance): bool
    {
        $this_model_class = get_class($this);
        $instance_class = get_class($instance);

        if ($this_model_class !== $instance_class) {
            throw new InvalidArgumentException(
                concat($this_model_class, ' Expected, ', $instance_class, ' Found Instead')
            );
        }

        $key = self::$primary_key;
        return $this->{$key} === $instance->{$key};
    }

    /**
     * Delete this model
     *
     * @return bool
     */
    public function remove()
    {
        $primary_key = static::getPrimaryKey();
        $entry_id = $this->{$primary_key};

        static::onBeforeDelete($this);

        $deleted = static::delete()
            ->where($primary_key, $entry_id)
            ->fulfil();

        if (!$deleted) {
            return false;
        }

        static::onDelete($entry_id);
        return true;
    }

    /**
     * Add data that will be returned when Model::data() is called
     *
     * @param string $key
     * @param callable $fn
     * @return void
     */
    public function withData(string $key, callable $fn)
    {
        $this->with_data[$key] = $fn;
    }

    /**
     * Set as new instance
     *
     * @return void
     */
    private function isNewInsance($status)
    {
        $this->_is_new = $status;
    }

    /**
     * Return all results from schema
     *
     * @param array $order Order methods
     * @see \Cube\Db\DBSelect::orderBY() method
     * 
     * @param array $opts
     * 
     * @return ModelCollection|array|null
     */
    public static function all(?array $order = null, ?array $opts = null)
    {
        $query = static::select()
            ->orderBy($order);

        return $opts ?
            call_user_func_array([$query, 'fetch'], $opts) : $query->fetchAll();
    }

    /**
     * Insert new entry into schema
     * 
     * @param array $entry Data to store
     * 
     * @return int Insert id
     */
    public static function createEntry(array $entry)
    {
        $entry_id = DB::table(static::$schema)->insert($entry);
        static::onCreate($entry_id);

        return $entry_id;
    }

    /**
     * Create Entry and return entry as Model instance
     *
     * @param array $entry
     * @return $this
     */
    public static function createObjectEntry(array $entry)
    {
        $classname = get_called_class();
        $instance = new $classname();

        every($entry, function ($value, $key) use (&$instance) {
            $instance->{$key} = $value;
        });

        $instance->save();
        return $instance;
    }

    /**
     * Delete from schema
     *
     * @return DBDelete
     */
    public static function delete(): DBDelete
    {
        return DB::table(static::$schema)->delete();
    }

    /**
     * Fetch data using passed primary key value
     * 
     * @param string|int $primary_key
     * @param array $fields Fields to retrieve
     * 
     * @return $this
     */
    public static function find($primary_key)
    {
        return static::select()
            ->where(static::getPrimaryKey(), $primary_key)
            ->fetchOne();
    }

    /**
     * Fetch all data using passed field value
     *
     * @param string $field Field name
     * @param mixed $value Field value
     * @param array|null $order Order methods
     * @param array|null $params Parameters
     * 
     * @return ModelCollection|array|null
     */
    public static function findAllBy($field, $value, $order = null, $params = null)
    {
        $query = static::select()
            ->where($field, $value)
            ->orderBy($order);

        if (!$params) {
            return call_user_func([$query, 'fetchAll']);
        }

        return call_user_func_array([$query, 'fetch'], $params);
    }

    /**
     * Fetch data using passed $field value
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * 
     * @return $this|null
     */
    public static function findBy($field, $value)
    {
        return static::select()
            ->where($field, $value)
            ->fetchOne();
    }

    /**
     * Fetch data using passed primary key value
     * 
     * @deprecated v0.9.8
     * @param string|int $primary_key
     * 
     * @return $this
     */
    public static function findByPrimaryKey($primary_key)
    {
        return self::find($primary_key);
    }

    /**
     * Find entry using primary key and delete
     * 
     * @param int|string $primary_key
     * 
     * @return int
     */
    public static function findByPrimaryKeyAndRemove($primary_key)
    {
        return DB::table(static::$schema)
            ->delete()
            ->where(static::getPrimaryKey(), $primary_key)
            ->fulfil();
    }

    /**
     * Find entry using primary and update entry data
     * 
     * @param string|int $primary_key
     * @param array $update New entry data
     * 
     * @return int
     */
    public static function findByPrimaryKeyAndUpdate($primary_key, array $update)
    {
        return DB::table(static::$schema)
            ->update($update)
            ->where(static::getPrimaryKey(), $primary_key)
            ->fulfil();
    }

    /**
     * Find or fail
     * 
     * callable $failed will be executed if $primary_key is not found
     *
     * @param mixed $primary_key
     * @param callable $failed
     * @return $this
     */
    public static function findOrFail($primary_key, callable $failed): ?self
    {
        $data = self::find($primary_key);

        if (!$data) {
            $failed($primary_key);
            return null;
        }

        return $data;
    }

    /**
     * Find by or failed
     * 
     * callable $failed will be executed if there is no field <> value match
     *
     * @param string $field
     * @param mixed $value
     * @param callable $failed
     * @return $this
     */
    public static function findByOrFail(string $field, $value, callable $failed): ?self
    {
        $data = self::findBy($field, $value);

        if (!$data) {
            $failed($value, $field);
            return null;
        }

        return $data;
    }

    /**
     * Fetch
     *
     * @param integer $count
     * @param integer $offset
     * @return ModelCollection|array|null
     */
    public static function fetch(int $count, int $offset = 0)
    {
        return static::select()->fetch($offset, $count);
    }

    /**
     * Instance from data
     *
     * @param object $data
     * @return $this
     */
    public static function fromData(string $classname, object $data)
    {
        /** @var $this */
        $instance = new $classname();
        $instance->isNewInsance(false);

        $fields = (array) $data;
        $data = array();
        $private_data = array();

        array_walk($fields, function ($_, $key) use ($classname, &$instance, &$fields, &$data, &$private_data) {

            $casted_value = $instance->checkCast(
                $fields,
                $key
            );

            if (in_array($key, $classname::$private_fields)) {
                return $private_data[$key] = $casted_value;
            }

            $data[$key] = $casted_value;
        });

        $instance->_data = $data;
        $instance->_data_private = $private_data;

        return $instance;
    }

    /**
     * Return the number of rows on table
     *
     * @return int
     */
    public static function getCount(): int
    {
        $key = static::getPrimaryKey();
        $res = DB::table(static::$schema)
            ->select(['count(' . $key . ') tcount'])
            ->fetchOne();

        return $res ? $res->tcount : 0;
    }

    /**
     * Return the number of rows on table based on specified field and value
     *
     * @param string $field Schema column name
     * @param mixed $value Value
     * @return int
     */
    public static function getCountBy($field, $value)
    {
        $key = static::getPrimaryKey();

        return DB::table(static::$schema)
            ->select(['count(' . $key . ') tcount'])
            ->where($field, $value)
            ->fetchOne()
            ->tcount;
    }

    /**
     * Return a raw query-able count query
     *
     * @return DBSelect
     */
    public static function getCountQuery()
    {
        $key = static::getPrimaryKey();
        return DB::table(static::$schema)
            ->select(["count({$key}) as count"]);
    }

    /**
     * Get first entry based on specified field
     * Or primary key if field is not specified
     *
     * @param string|null $field
     * 
     * @return object|null
     */
    public static function getFirst($field = null)
    {
        return static::select()
            ->getFirst(($field ?? static::getPrimaryKey()));
    }

    /**
     * Get last entry based on specified field
     * Or primary key if field is not specified
     *
     * @param string|null $field
     * 
     * @return object|null
     */
    public static function getLast($field = null)
    {
        return static::select()
            ->getLast(($field ?? static::getPrimaryKey()));
    }

    /**
     * Get model's schema name
     *
     * @return string
     */
    public static function getSchemaName(): string
    {
        return static::$schema;
    }

    /**
     * Schema's primary key
     *
     * @return string
     */
    public static function getPrimaryKey()
    {
        return static::$primary_key;
    }

    /**
     * Sum schema by field
     *
     * @param string $field
     * @return float Total sum
     */
    public static function getSumByField(string $field)
    {
        return DB::table(static::$schema)->sum($field);
    }

    /**
     * Lock
     *
     * @return DBSelect
     */
    public static function lock(): DBSelect
    {
        return self::select()->lock();
    }

    /**
     * Query schema's table directly
     *
     * @return DBTable
     */
    public static function query(): DBTable
    {
        return DB::table(static::$schema);
    }

    /**
     * Update table rows
     *
     * @param array $fields
     * @return DBUpdate
     */
    public static function update(array $fields)
    {
        return self::query()->update($fields);
    }

    /**
     * Update field where there is a matching data or create new entry if $fields does not match
     *
     * @param array $fields
     * @param array $data
     * @return int
     */
    public static function updateOrCreate(array $fields, array $data)
    {
        $query = self::update($data);

        array_walk($fields, function ($value, $name) use ($query) {
            $query->where($name, $value);
        });

        $rows = $query->fulfil();

        if (!$rows) {
            $new_data = array_merge($fields, $data);
            return self::createEntry($new_data);
        }

        return $rows;
    }

    /**
     * Run custom queries on model's schema
     * @var array ...$args
     * @return DBSelect
     */
    public static function select(...$args): DBSelect
    {
        $select = new DBSelect(
            static::$schema,
            count($args) ? $args : self::fields(),
            get_called_class()
        );

        return $select;
    }

    /**
     * Search for matching fields
     *
     * @param string $field Field to search
     * @param int|null $offset Offset
     * @param int|null $limit Limit
     * 
     * @return object[]|null
     */
    public static function search($field, $keyword, $limit = null, $offset = null)
    {
        $query = static::select()
            ->whereLike($field, $keyword);

        if (!$limit) {
            return $query->fetchAll();
        }

        $offset = $offset ?? 0;
        return $query->fetch($offset, $limit);
    }

    /**
     * Sum query
     *
     * @param string $field
     * @return DBSelect
     */
    public static function sum(string $field)
    {
        return self::query()
            ->select([concat('SUM(', $field, ') total')]);
    }

    /**
     * Model where statement
     *
     * @param string[] ...$args
     * @return DBSelect
     */
    public static function where(...$args)
    {
        return self::select()->where(...$args);
    }

    /**
     * Get fields with property
     *
     * @param array ...$fields
     * @return array
     */
    public static function withFields(...$fields): array
    {
        return array_merge(
            static::$fields,
            $fields
        );
    }

    /**
     * Method gets called when a new entry is created
     *
     * @param mixed $id
     * @return mixed
     */
    protected static function onCreate($id)
    {
        return $id;
    }

    /**
     * Method gets called before item is deleted
     *
     * @param mixed $id
     * @return mixed
     */
    protected static function onBeforeDelete($id)
    {
        return $id;
    }

    /**
     * Method gets called when entry is deleted
     *
     * @param mixed $id
     * @return mixed
     */
    protected static function onDelete($id)
    {
        return $id;
    }

    /**
     * Method gets called when an entry is updated
     *
     * @param mixed $id
     * @return mixed
     */
    protected static function onUpdate($id)
    {
        return $id;
    }

    /**
     * Check data type cast
     *
     * @param array $params
     * @param string $field
     * @return mixed
     */
    private function checkCast($params, $field)
    {
        $cast = $this->cast;
        $value = isset($params[$field]) ? $params[$field] : null;

        if (!count($cast)) {
            return $value;
        }

        $allowed_casts = array(
            self::CAST_TYPE_BOOLEAN,
            self::CAST_TYPE_STRING,
            self::CAST_TYPE_FLOAT,
            self::CAST_TYPE_INT
        );

        $masked_cast = array_find_all($cast, function ($value, $key) {
            return is_numeric($key);
        });

        if ($masked_cast) {
            every($masked_cast, function ($fields_list, $type) use (&$cast) {
                unset($cast[$type]);
                $fields = explode('|', $fields_list);

                every($fields, function ($field) use (&$cast, $type) {
                    $cast[$field] = $type;
                });
            });
        }

        $selected_cast_type = $cast[$field] ?? null;

        if (!$selected_cast_type) {
            return $value;
        }

        if (!in_array($selected_cast_type, $allowed_casts)) {
            throw new ModelException(
                concat('Data type "', $selected_cast_type, '" is not specified')
            );
        }

        $casts = array(
            self::CAST_TYPE_INT => (int) $value,
            self::CAST_TYPE_FLOAT => (float) $value,
            self::CAST_TYPE_STRING => (string) $value,
            self::CAST_TYPE_BOOLEAN => (bool) $value
        );

        return $casts[$selected_cast_type];
    }

    /**
     * Parse fields to readable
     * 
     * @return array
     */
    private static function fields()
    {
        $fields = static::$fields;
        $private_fields = static::$private_fields;
        $primary_key = static::$primary_key;

        $rows = array_unique([
            $primary_key,
            ...$fields,
            ...$private_fields
        ]);

        if (!count($rows)) {
            return ['*'];
        }

        return $rows;
    }
}
