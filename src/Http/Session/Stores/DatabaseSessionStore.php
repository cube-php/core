<?php

namespace Cube\Http\Session\Stores;

use Cube\App\App;
use Cube\Http\Session\SessionStoreInterface;
use Cube\Modules\Db\DBConnection;
use Cube\Modules\Db\DBTable;

class DatabaseSessionStore implements SessionStoreInterface
{
    protected static string $schema_name = 'cube_sessions';

    public function __construct() {}

    /**
     * Destroy session by id
     *
     * @param string $id Session id
     * @return void
     */
    public function destroy(string $id): void
    {
        self::getTable()
            ->delete()
            ->where('id', $id)
            ->fulfil();
    }

    /**
     * Read session data by id
     *
     * @param string $id Session id
     * @return array
     */
    public function read(string $session_id): array
    {
        $session = self::getTable()
            ->select(['data'])
            ->where('id', $session_id)
            ->fetchOne();

        return $session
            ? json_decode(
                base64_decode($session->data),
                true
            ) : [];
    }

    /**
     * Write session data by id
     *
     * @param string $id Session id
     * @param array $session_data Session data
     * @return void
     */
    public function write(string $id, array $session_data): void
    {
        self::getTable()
            ->insert([
                'data' => base64_encode(
                    json_encode($session_data)
                ),
                'updated_at' => getnow(),
                'created_at' => getnow(),
            ], [
                'id' => $id
            ]);
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
            $table->field('user_id')->int()->nullable();
            $table->field('data')->text();
            $table->field('created_at')->datetime();
            $table->field('updated_at')->datetime()->nullable();
        });

        $table->addIndex('idx_id', ['id']);
        $table->addIndex('idx_upd', ['updated_at']);
        $table->addIndex('idx_user', ['user_id']);
    }
}
