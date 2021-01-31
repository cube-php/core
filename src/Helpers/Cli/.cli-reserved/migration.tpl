<?php

namespace App\Migrations;

use Cube\Interfaces\MigrationInterface;
use Cube\Modules\DB;
use Cube\Modules\Db\DBTableBuilder;

class {className} implements MigrationInterface
{
    private const NAME = '{name}';

    /**
     * Action to create migration
     *
     * @return void
     */
    public static function up()
    {
        DB::table(self::NAME)
            ->create(function (DBTableBuilder $table) {
                $table->field('id')->int()->increment();
            });
    }

    /**
     * Empty data in schema
     *
     * @return void
     */
    public static function empty()
    {
        DB::table(self::NAME)->truncate();
    }

    /**
     * Drop table
     *
     * @return void
     */
    public static function down()
    {
        DB::table(self::NAME)->drop();
    }
}