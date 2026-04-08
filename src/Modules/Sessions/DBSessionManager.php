<?php

namespace Cube\Modules\Sessions;

use Cube\App\App;
use Cube\Modules\Db\DBConnection;
use Cube\Modules\Db\DBTable;
use SessionHandlerInterface;

class DBSessionManager implements SessionHandlerInterface
{
    protected static string $schema_name = 'cube_sessions';

    public function __construct() {}

    public function close(): bool
    {
        return true;
    }

    public function destroy($id): bool
    {
        self::getTable()
            ->delete()
            ->where('id', $id)
            ->fulfil();
        return true;
    }

    public function gc($maxlifetime): int|false
    {
        $old = gettime(time() - $maxlifetime);

        return self::getTable()
            ->delete()
            ->where('updated_at', '<', $old)
            ->fulfil() ?? false;
    }

    public function open($save_path, $session_name): bool
    {
        return true;
    }

    public function read($session_id): string
    {
        $session = self::getTable()
            ->select(['data'])
            ->where('id', $session_id)
            ->fetchOne();

        return $session ? base64_decode($session->data) : '';
    }

    public function write($id, $session_data): bool
    {
        self::getTable()
            ->insert([
                'data' => base64_encode($session_data),
                'updated_at' => getnow(),
                'created_at' => getnow(),
            ], [
                'id' => $id
            ]);

        return true;
    }

    public function init()
    {
        return $this->up();
    }

    private static function getTable(): DBTable
    {
        return new DBTable(
            self::$schema_name,
            self::getConnection()
        );
    }

    private static function getConnection(): DBConnection
    {
        $config = App::getConfig('app');
        $connection_name = $config['session_connection'] ?? null;
        return DBConnection::connection($connection_name);
    }

    private function up()
    {
        $table = self::getTable();
        $table->build(function ($table) {
            $table->field('id')->varchar()->primary();
            $table->field('data')->text();
            $table->field('created_at')->datetime();
            $table->field('updated_at')->datetime()->nullable();
        });

        $table->addIndex('idx_id', ['id']);
        $table->addIndex('idx_upd', ['updated_at']);
    }
}
