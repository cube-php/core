<?php

namespace Cube\Http;

use Cube\Modules\DB;
use Cube\Modules\Db\DBTable;
use Cube\Modules\Db\DBSelect;
use Cube\Interfaces\ModelInterface;
use Cube\Modules\Db\DBUpdate;
use Cube\Modules\Db\DBDelete;
use InvalidArgumentException;
use ReflectionClass;

class Model implements ModelInterface
{

    /**
     * Model database table name
     * 
     * @var string
     */
    protected static $schema;

    /**
     * Model provider
     *
     * @var string|null
     */
    protected static $provider = null;

    /**
     * Selectable fields from specified $schema
     * 
     * @var array
     */
    protected static $fields = array();

    /**
     * Primary key field name
     * 
     * @var string
     */
    protected static $primary_key = 'id';

    /**
     * Model data
     *
     * @var object
     */
    private $_data;

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
    private $_relations = array();

    /**
     * Model constructor
     *
     * @param object $id
     */
    public function __construct(object $data)
    {
        $this->_data = $data;
    }

    /**
     * Getter
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if(isset($this->_data->{$name})) {
            return $this->_data->{$name};
        }

        if(method_exists($this, $name)) {
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
        return property_exists($this->_data, $name);
    }

    /**
     * Add an update
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->_updates[$name] = $value;
    }

    /**
     * Save updates
     *
     * @return bool
     */
    public function save(): bool
    {
        $key = self::getPrimaryKey();
        $saved = !!static::update($this->_updates)
                    ->where($key, $this->{$key})
                    ->fulfil();

                            
        if($saved) {
            $old_data = (array) $this->_data;
            array_walk($this->_updates, function ($value, $field) use(&$old_data) {
                $old_data[$field] = $value;
            });

            $this->_data = (object) $old_data;
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
        $class = new ReflectionClass($model);

        if(!$class->implementsInterface(ModelInterface::class)) {
            throw new InvalidArgumentException('Invalid model class');
        }

        $field_name = $name ?: self::$primary_key;

        if(isset($this->_relations[$field_name])) {
            return $this->_relations[$field_name];
        }

        $result = $name ? $model::findBy($field_name, $this->{$field}) : $model::find($this->{$field});
        
        $this->_relations[$field_name] = $result;
        return $result;
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
        $class = new ReflectionClass($model);
        
        if(!$class->implementsInterface(ModelInterface::class)) {
            throw new InvalidArgumentException('Invalid model class');
        }

        $field_name = $name ?: self::$primary_key;

        if(isset($this->_relations[$field_name])) {
            return $this->_relations[$field_name];
        }

        $result = $model::findAllBy($field_name, $this->{$field});

        $this->_relations[$field_name] = $result;
        return $result;
    }

    /**
     * Model content
     *
     * @return object
     */
    public function data(): object
    {
        return $this->_data;
    }

    /**
     * Return all results from schema
     *
     * @param array $order Order methods
     * @see \Cube\Db\DBSelect::orderBY() method
     * 
     * @param array $opts
     * 
     * @return array|null
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
        return DB::table(static::$schema)->insert($entry);
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
     * @return array
     */
    public static function findAllBy($field, $value, $order = null, $params = null)
    {
        $query = static::select()
                 ->where($field, $value)
                 ->orderBy($order);

        if(!$params) {
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
     * Fetch
     *
     * @param integer $count
     * @return self[]
     */
    public static function fetch(int $count)
    {
        return static::select()->fetch($count);
    }

    /**
     * Return the number of rows on table
     *
     * @return int
     */
    public static function getCount()
    {
        $key = static::getPrimaryKey();
        return DB::table(static::$schema)
                ->select(['count('. $key .') tcount'])
                ->fetchOne()
                ->tcount;
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
                ->select(['count('. $key .') tcount'])
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
        
        if(!$rows) {
           $new_data = array_merge($fields, $data);
            return self::createEntry($new_data);
        }

        return $rows;
    }

    /**
     * Run custom queries on model's schema
     *
     * @return DBSelect
     */
    public static function select(): DBSelect
    {
        $select = new DBSelect(
            static::$schema,
            self::fields(),
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

        if(!$limit) {
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
                ->select(["SUM(${field}) total"]);
    }

    /**
     * Parse fields to readable
     * 
     * @return array
     */
    private static function fields()
    {
        $fields = static::$fields;
        $primary_key = static::$primary_key;

        $rows = [$primary_key, ...$fields];

        if(!count($rows)) {
            return ['*'];
        }

        return $rows;
    }
}