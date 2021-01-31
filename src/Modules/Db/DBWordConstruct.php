<?php

namespace Cube\Modules\Db;

class DBWordConstruct
{

    /**
     * Create alter add table statement
     * 
     * @param string $table_name Table name
     * @param string $structure Table structure
     * 
     * @return string
     */
    public static function alterTableAdd($table_name, $structure)
    {
        return 'ALTER TABLE ' . $table_name . ' ADD ' .$structure;
    }

    /**
     * Alter table and add index
     *
     * @param string $table_name Table name
     * @param string $index_name Index name
     * @param string $field_name Field name
     * @return string
     */
    public static function alterTableAddIndex($table_name, $index_name, $field_name)
    {
        return concat('ALTER TABLE ', $table_name, ' ADD INDEX ', $index_name, ' (', $field_name, ')');
    }

    /**
     * Create alter remove table field
     * 
     * @param string $table_name Table name
     * @param string $field_name Field nme
     * 
     * @return string
     */
    public static function alterTableRemove($table_name, $field_name)
    {
        return 'ALTER TABLE ' . $table_name . ' DROP ' .$field_name;
    }

    /**
     * Create create table statement
     * 
     * @param string $table_name
     * @param string $structure
     * 
     * @return string
     */
    public static function createTable($table_name, $structure)
    {
        return 'CREATE TABLE ' . $table_name . '(' . $structure . ')';
    }

    /**
     * Construct describe table statement
     * 
     * @param string $table_name
     * 
     * @return string
     */
    public static function describe($table_name)
    {
        return 'DESCRIBE ' . $table_name;
    }

    /**
     * Drop index
     *
     * @param string $table_name Table name
     * @param string $index_name Index name
     * @return string
     */
    public static function dropIndex($table_name, $index_name)
    {
        return concat('DROP INDEX ', ' ', $index_name, ' ON ', $table_name);
    }

    /**
     * Construct drop table statement
     * 
     * @param string $table_name
     * 
     * @return string
     */
    public static function dropTable($table_name)
    {
        return 'DROP TABLE ' . $table_name;
    }

    /**
     * Rename table statement
     *
     * @param string $old_name Old table name
     * @param string $new_name New name for table
     * @return string
     */
    public static function renameTable($old_name, $new_name)
    {
        return 'RENAME TABLE ' . $old_name . ' to ' . $new_name;
    }

    /**
     * Construct truncate table
     * 
     * @param string $table_name
     * 
     * @return string
     */
    public static function truncateTable($table_name)
    {
        return 'TRUNCATE '. $table_name;
    }
}