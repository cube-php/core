<?php

namespace App\Migrations;

use Cube\Interfaces\MigrationInterface;
use Cube\Modules\DB;
use Cube\Modules\Db\DBTableBuilder;

class {className} implements MigrationInterface
{
    private const NAME = '{name}';

    private const CONNECTION_NAME = 'default'

    /**
     * Action to create migration
     *
     * @return void
     */
    public static function up()
    {
        Database::from(self::CONNECTION_NAME)
            ->table(self::NAME)
            ->build(function (DBTableBuilder $builder) {
                $builder->field('id')->int()->increment();
            });
    }

    /**
     * Empty data in schema
     *
     * @return void
     */
    public static function empty()
    {
        Database::from(self::CONNECTION_NAME)
            ->table(self::NAME)
            ->truncate();
    }

    /**
     * Drop table
     *
     * @return void
     */
    public static function down()
    {
        Database::from(self::CONNECTION_NAME)
            ->table(self::NAME)
            ->drop();
    }
}