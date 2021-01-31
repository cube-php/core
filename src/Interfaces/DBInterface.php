<?php

namespace Cube\Interfaces;

interface DBInterface
{

    public static function escape($string);

    public static function hasTable($table_name);

    public static function insert($table, $params);

    public static function lastInsertId();

    public static function statement($query, $params);

    public static function table($table_name);

    public static function tables();
    
    public static function update($table_name, $params);
}