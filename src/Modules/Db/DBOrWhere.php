<?php

namespace Cube\Modules\Db;

use Cube\Modules\Db\DBQueryBuilder;

class DBOrWhere
{

    /**
     * Builder
     * 
     * @var DBQueryBuilder
     */
    private $_builder;

    /**
     * Statement vars
     * 
     * @var string[]
     */
    private $_statement_vars = [];

    /**
     * Joiner
     * 
     * @var string
     */
    private $joiner;

    /**
     * Class constructor
     * 
     * @param DBQueryBuilder $builder Query Builder
     */
    public function __construct(DBQueryBuilder $builder)
    {
        $this->_builder = $builder;
        $this->_builder->joinSql(null, '(');
    }

    /**
     * Class destructor
     * 
     * Completes sql query build
     */
    public function __destruct()
    {
        $statement_vars = implode(' ' . $this->joiner . ' ', $this->_statement_vars);
        $this->_builder->joinSql(null, $statement_vars, ')');
    }

    /**
     * Push query statments
     * 
     * @param string[] $args Arguments
     * 
     * @return self
     */
    public function where(...$args)
    {
        $arg = $this->_builder->parseArgs($args);
        $this->pushArgs($arg->field, $arg->operator, $arg->value);
        $this->joiner = 'AND';
        return $this;
    }
    
    /**
     * Push query statments
     * 
     * @param string[] $args Arguments
     * 
     * @return self
     */
    public function or(...$args)
    {
        $arg = $this->_builder->parseArgs($args);
        $this->pushArgs($arg->field, $arg->operator, $arg->value);
        $this->joiner = 'OR';
        return $this;
    }

    /**
     * Push arguments to statement
     * 
     * @param string $field Field name
     * @param string $operator Operator value
     * @param string $value Field Value
     * 
     * @return void
     */
    private function pushArgs($field, $operator, $value)
    {
        $this->_statement_vars[] = "{$field} {$operator} {$value}";
    }
}