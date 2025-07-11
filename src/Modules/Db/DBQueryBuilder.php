<?php

namespace Cube\Modules\Db;

use InvalidArgumentException;

use Cube\Modules\Db\DBOrWhere;
use Cube\Modules\Db\DBQueryGroup;

use Cube\Modules\Db\DBSelect;
use Cube\Modules\Db\DBUpdate;
use Cube\Modules\Db\DBDelete;

class DBQueryBuilder
{
    /**
     * Sql query
     * 
     * @var string
     */
    protected $sql_query = '';

    /**
     * Query parameters
     * 
     * @var string
     */
    protected $parameters = array();

    /**
     * Set if where statement has been called
     *
     * @var boolean
     */
    protected $has_called_where = false;

    /**
     * Wrapper class name
     *
     * @var string|null
     */
    protected $bundle = null;

    /**
     * Field value inidcator
     *
     * @var string
     */
    protected $_value_prefix = '@';

    /**
     * Class to string
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->getSqlQuery();
    }

    /**
     * And statement
     *
     * @param args ...$args
     * @return $this
     */
    public function and(...$args)
    {
        $args = $this->parseArgs($args);
        $this->joinSql(null, 'AND', $args->field, $args->operator, $args->value);
        return $this;
    }

    /**
     * And between query
     * 
     * @param string $field Field name
     * @param array $values
     * 
     * @return $this
     */
    public function andBetween($field, $values)
    {
        return $this->between('AND', $field, $values);
    }

    /**
     * And exists statement
     * 
     * @param callable $group
     * @return $this
     */
    public function andExists($group)
    {
        return $this->exists('AND', $group);
    }

    /**
     * AND IN statement
     * 
     * @param string $field
     * @param array|callable $group
     * @return $this
     */
    public function andIn($field, $group)
    {
        return $this->in('AND', $field, $group);
    }

    /**
     * And LIKE statement
     *
     * @param string $field
     * @param string $keyword
     * @return $this
     */
    public function andLike($field, $keyword)
    {
        return $this->like('AND', $field, $keyword);
    }

    /**
     * AND NOT IN statement
     * 
     * @param string $field Field name
     * @param array|callable $group
     * @return $this
     */
    public function andNotIn($field, $group)
    {
        return $this->notIn('AND', $field, $group);
    }

    /**
     * AND not null
     * 
     * @param string $field Field name
     * @return $this
     */
    public function andNotNull($field)
    {
        return $this->notNull('AND', $field);
    }

    /**
     * And where statement
     * 
     * @param string[] $args Arguments
     * @return $this
     */
    public function andWhere(...$args)
    {
        return $this->whereGroup('AND', $args);
    }

    /**
     * Return sql parameteres
     * 
     * @return array
     */
    public function getSqlParameters()
    {
        return $this->parameters;
    }

    /**
     * Add limit statement
     *
     * @param integer $limit
     * @return $this
     */
    public function limit(int $limit, int $offset = 0)
    {
        if ($offset > 0) {
            $this->joinSql(null, 'LIMIT', $this->addParam($offset), ',', $this->addParam($limit));
            return $this;
        }

        $this->joinSql(null, 'LIMIT', $this->addParam($limit));
        return $this;
    }

    /**
     * Having statement
     *
     * @param array ...$args
     * @return $this
     */
    public function having(...$args)
    {
        $args = $this->parseArgs($args);
        return $this->joinSql(null, 'HAVING', $args->field, $args->operator, $args->value);
    }

    /**
     * Or statement
     * 
     * @return $this
     */
    public function or(...$args)
    {
        $args = $this->parseArgs($args);
        $this->joinSql(null, 'OR', $args->field, $args->operator, $args->value);
        return $this;
    }

    /**
     * Or between query
     * 
     * @param string $field Field name
     * @param array $values
     * @return $this
     */
    public function orBetween($field, $values)
    {
        return $this->between('OR', $field, $values);
    }

    /**
     * OR EXISTS statement
     * 
     * @param callable $group
     * @return $this
     */
    public function orExists($group)
    {
        return $this->exists('OR', $group);
    }

    /**
     * OR IN statement
     * 
     * @param string $field
     * @param array|callable $group
     * @return $this
     */
    public function orIn($field, $group)
    {
        return $this->in('OR', $field, $group);
    }

    /**
     * Or LIKE statement
     *
     * @param string $field
     * @param string $keyword
     * @return $this
     */
    public function orLike($field, $keyword)
    {
        return $this->like('OR', $field, $keyword);
    }

    /**
     * OR NOT IN statememnt
     * 
     * @param string $field Field name
     * @param array|callable $group
     * @return $this
     */
    public function orNotIn($field, $group)
    {
        return $this->notIn('OR', $field, $group);
    }

    /**
     * OR not null
     * 
     * @param string $field Field name
     * @return $this
     */
    public function orNotNull($field)
    {
        return $this->notNull('OR', $field);
    }

    /**
     * Or is null
     *
     * @param string $field
     * @return $this
     */
    public function orNull($field)
    {
        return $this->null('OR', $field);
    }

    /**
     * Or where statement
     * 
     * @param string[] $args Arguments
     * @return $this
     */
    public function orWhere(...$args)
    {
        return $this->whereGroup('OR', $args);
    }

    /**
     * Where statement
     * 
     * @param mixed[] ...$args Where params
     * @return $this
     */
    public function where(...$args)
    {
        if ($this->has_called_where) {
            return call_user_func_array([$this, 'and'], $args);
        }

        $args = $this->parseArgs($args);
        $this->joinSql(null, 'WHERE', $args->field, $args->operator, $args->value);
        $this->has_called_where = true;
        return $this;
    }

    /**
     * Where between query
     * 
     * @param string $field Field name
     * @param array $values
     * @return $this
     */
    public function whereBetween($field, $values)
    {
        if ($this->has_called_where) {
            return call_user_func_array([$this, 'andBetween'], [$field, $values]);
        }

        $this->has_called_where = true;
        return $this->between('WHERE', $field, $values);
    }

    /**
     * Where exists statement
     * 
     * @param callable $group
     * @return $this
     */
    public function whereExists($group)
    {
        if ($this->has_called_where) {
            return call_user_func_array([$this, 'andExists'], [$group]);
        }

        $this->has_called_where = true;
        return $this->exists('WHERE', $group);
    }

    /**
     * Where in statement
     * 
     * @param string $field
     * @param array|callable $group
     * @return $this
     */
    public function whereIn($field, $group)
    {
        if ($this->has_called_where) {
            return call_user_func_array([$this, 'andIn'], [$field, $group]);
        }

        $this->has_called_where = true;
        return $this->in('WHERE', $field, $group);
    }

    /**
     * Where like statement
     *
     * @param string $field
     * @param mixed $keyword
     * @return $this
     */
    public function whereLike($field, $keyword)
    {
        if ($this->has_called_where) {
            return call_user_func_array([$this, 'andLike'], [$field, $keyword]);
        }

        $this->has_called_where = true;
        return $this->like('WHERE', $field, $keyword);
    }

    /**
     * Where not in
     * 
     * @param string $field Field name
     * @param array|callable $group
     * @return $this
     */
    public function whereNotIn($field, $group)
    {
        if ($this->has_called_where) {
            return call_user_func_array([$this, 'andNotIn'], [$field, $group]);
        }

        $this->has_called_where = true;
        return $this->notIn('WHERE', $field, $group);
    }

    /**
     * Where not null
     * 
     * @param string $field Field name
     * @return $this
     */
    public function whereNotNull($field)
    {
        if ($this->has_called_where) {
            return call_user_func_array([$this, 'andNotNull'], [$field]);
        }

        $this->has_called_where = true;
        return $this->notNull('WHERE', $field);
    }

    /**
     * Where null
     * 
     * @param string $field
     * @return $this
     */
    public function whereNull($field)
    {
        $this->null('WHERE', $field);
        return $this;
    }

    /**
     * Raw statement with WHERE/AND prefix
     *
     * @param string $statement
     * @param array $params
     * @return $this
     */
    public function whereRaw($statement, array $params = [])
    {
        $prefix = $this->has_called_where ? 'AND' : 'WHERE';
        $this->has_called_where = true;

        return $this->raw(
            concat($prefix, ' ', $statement),
            $params
        );
    }

    /**
     * Raw statement
     * 
     * @param string    $statement Query
     * @param array     $params Parameters
     * @return $this
     */
    public function raw($statement, array $params = [])
    {
        $this->joinSql(null, $statement);
        $this->bindParam($params);
        return $this;
    }

    /**
     * Between statement initiator
     * 
     * @param string $key Key
     * @param string $field Field
     * @param array $values
     * @return $this
     */
    protected function between($key, $field, $values)
    {
        if (count($values) !== 2) {
            throw new InvalidArgumentException('Where between values should contain an array with two fields');
        }

        $col1 = $this->addParam($values[0]);
        $col2 = $this->addParam($values[1]);

        $this->joinSql(null, $key, $field, 'BETWEEN', $col1, 'AND', $col2);
        return $this;
    }

    /**
     * EXISTS statement initiatiot
     * 
     * @param string $key KEYWORD
     * @param callable $group Callback function
     * 
     * @return DBQueryBuilder
     */
    protected function exists($key, $group)
    {
        $this->joinSql(null, $key, 'EXISTS', null);
        $group(new DBQueryGroup($this));

        return $this;
    }

    /**
     * SQL IN statement initiator
     * 
     * @param string $sql Keyword
     * @param string $field
     * @param array|callable $group
     * 
     * @return DBQueryBuilder
     */
    protected function in($key, $field, $group)
    {
        $this->joinSql(null, $key, $field, 'IN', null);
        return $this->parseInGroup($group);
    }

    /**
     * SQL like statement initiator
     *
     * @param string $key
     * @param string $field
     * @param mixed $key
     * @return DBQueryBuilder
     */
    protected function like($key, $field, $keyword)
    {
        $filteredKey = $this->addParam("%{$keyword}%");
        $this->joinSql(null, $key, $field, 'LIKE', $filteredKey);
        return $this;
    }

    /**
     * NULL statement initiator
     * 
     * @param string $key
     * @param string $field
     * 
     * @return DBQueryBuilder
     */
    protected function null($key, $field)
    {
        $this->{$key}(concat($this->_value_prefix, $field), 'IS', 'NULL');
        return $this;
    }

    /**
     * SQL NOT IN statement initiator
     * 
     * @param string $sql Keyword
     * @param string $field
     * @param array|callable $group
     * 
     * @return DBQueryBuilder
     */
    protected function notIn($key, $field, $group)
    {
        $this->joinSql(null, $key, $field, 'NOT IN', null);
        return $this->parseInGroup($group);
    }

    /**
     * SQL NOT NULL statement initiator
     * 
     * @param string $key Statement keyword
     * @param string $field Field to compare
     * 
     * @return DBQueryBuilder
     */
    protected function notNull($key, $field)
    {
        $this->{$key}(concat($this->_value_prefix, $field), 'IS NOT', 'NULL');
        return $this;
    }

    /**
     * Where group initiator
     * 
     * @param string $key Statement
     * @param string[] $args Arguments
     * 
     * @return DBQueryBuilder
     */
    protected function whereGroup($key, $args)
    {
        $this->joinSql(null, $key, null);
        $num_args = count($args);

        if (!$num_args or $num_args > 3) {
            throw new InvalidArgumentException('The number of arguments for method "whereGroup" should not exceed 3');
        }

        if ($num_args == 1 && !is_callable($args[0])) {
            throw new InvalidArgumentException('whereGroup has only one argument and it should be a function');
        }

        if ($num_args == 1) {
            $args[0](new DBOrWhere($this));
            return $this;
        }

        $re_args = $this->parseArgs($args);
        $this->joinSql($re_args->field, $re_args->operator, $re_args->value);
        return $this;
    }

    /**
     * Add new binding param
     * 
     * @param string|int $value
     * 
     * @return string placeholder
     */
    protected function addParam($value)
    {
        $this->parameters[] = $value;
        return '?';
    }

    /**
     * Add new binding param
     * 
     * @param array $value
     * 
     * @return string placeholder
     */
    protected function bindParam(array $value)
    {
        foreach ($value as $val) {
            $this->addParam($val);
        }
    }

    /**
     * Return built sql query
     * 
     * @return string
     */
    protected function getSqlQuery()
    {
        return $this->sql_query;
    }

    /**
     * Prepend
     *
     * @param array ...$args
     * @return self
     */
    public function prependSql(...$args)
    {
        $old_query = $this->sql_query;
        $this->sql_query = implode(' ', $args) . $old_query;
        return $this;
    }

    /**
     * Merge query
     * 
     * @return void
     */
    public function joinSql(...$args)
    {
        $this->sql_query .= implode(' ', $args);
        return $this;
    }

    /**
     * Parse args
     * 
     * @param array $args
     */
    public function parseArgs($args)
    {
        $num_args = count($args);

        if ($num_args < 2 || $num_args > 3) {
            throw new InvalidArgumentException('Arguments should not be less than 2 and not exceed 3');
        }

        $has_operator = $num_args == 3;
        $raw_field = $args[0];

        $operator = $has_operator ? $args[1] : '=';
        $value = $has_operator ? $args[2] : $args[1];

        $value_prefix = $this->_value_prefix;
        $value_prefix_length = strlen($value_prefix);

        $is_raw = substr($raw_field, 0, $value_prefix_length) === $value_prefix;
        $param_value = $is_raw ? $value : $this->addParam($value);
        $field = $is_raw ? substr($raw_field, $value_prefix_length) : $raw_field;

        return (object) array(
            'operator' => $operator,
            'field' => $field,
            'value' => $param_value
        );
    }

    /**
     * Parse in group argument
     * 
     * @return DBQueryBuilder
     */
    private function parseInGroup($group)
    {
        if (!is_callable($group) && !is_array($group)) {
            throw new InvalidArgumentException('whereNotIn\'s 2nd argument must either be an array or a callback function');
        }

        if (is_callable($group)) {
            $group(new DBQueryGroup($this));
        }

        if (is_array($group)) {
            $this->joinSql('(', implode(', ', $group), ')');
        }

        return $this;
    }
}
