<?php

namespace Cube\Queue\Migrations;

use Cube\App\App;
use Cube\Modules\Db\DBConnection;
use Cube\Modules\Db\DBTableBuilder;
use Cube\Modules\Migration;

class JobsMigration extends Migration
{
    protected static string $name = 'cube_jobs';

    protected static function getConnection(): DBConnection
    {
        return DBConnection::connection(
            App::getConfig('queue.connection')
        );
    }

    public static function up()
    {
        $table = static::getTable();
        $table->build(function (DBTableBuilder $builder) {
            $builder->field('id')->int()->increment();
            $builder->field('group_name')->varchar()->nullable();
            $builder->field('payload')->longtext();
            $builder->field('attempts')->int()->default(0);
            $builder->field('reserved_at')->datetime()->nullable();
            $builder->field('available_at')->datetime();
        });

        $table->addIndex('idx_grp', ['group_name']);
        $table->addIndex('idx_pop', ['reserved_at', 'available_at', 'group_name']);
    }

    public static function empty()
    {
        static::getTable()->truncate();
    }

    public static function down()
    {
        static::getTable()->drop();
    }
}
