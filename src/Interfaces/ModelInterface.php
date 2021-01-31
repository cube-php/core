<?php

namespace Cube\Interfaces;

use Cube\Modules\Db\DBDelete;
use Cube\Modules\Db\DBSelect;
use Cube\Modules\Db\DBTable;

interface ModelInterface
{
    public static function all(?array $order = null, ?array $opts = null);
    
    public static function createEntry(array $entry);
    
    public static function delete(): DBDelete;

    public static function find($primary_key);
    
    public static function findAllBy($field, $value, $order = null, $params = null);
    
    public static function findBy($field, $value);
    
    public static function findByPrimaryKey($primary_key);
    
    public static function findByPrimaryKeyAndRemove($primary_key);
    
    public static function findByPrimaryKeyAndUpdate($primary_key, array $update);
    
    public static function getCount();
    
    public static function getCountBy($field, $value);
    
    public static function getCountQuery();
    
    public static function getFirst($field = null);
    
    public static function getLast($field = null);
    
    public static function query(): DBTable;
    
    public static function select(): DBSelect;
    
    public static function search($field, $keyword, $limit = null, $offset = null);

    public static function sum(string $field);
}