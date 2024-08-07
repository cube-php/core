<?php

namespace Cube\Modules;

use Cube\App\App;
use Cube\Modules\DB;
use Cube\Modules\Db\DBConnection;
use Cube\Modules\Db\DBTable;
use Cube\Tools\Auth;
use SessionHandlerInterface;

class SessionManager implements SessionHandlerInterface
{
    protected static string $schema_name = 'sessions';

    /**
     * Set if other session manager activities can proceed
     *
     * @var boolean
     */
    private static $can_run = false;

    /**
     * Class constructor
     * 
     * Check if the session table has been created
     */
    public function __construct()
    {
        /**
         * Switched the initialization to be powered by the 
         * Command line to free up system
         * 
         * $this->up();
         */
    }

    /**
     * On session close
     * 
     * @return bool
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * On destroy session
     * 
     * @return bool
     */
    public function destroy($session_id): bool
    {
        self::getTable()
            ->delete()
            ->where('id', $session_id)
            ->fulfil();
        return true;
    }

    /**
     * Session
     * 
     * @param int $maxlifetime
     * 
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function gc($maxlifetime): bool
    {
        $old = time() - $maxlifetime;

        self::getTable()
            ->delete()
            ->where('UNIX_TIMESTAMP(last_update)', '<', $old)
            ->fulfil();

        return true;
    }

    /**
     * On session open
     * 
     * @param string $save_path
     * @param string $session_id Session Id
     * 
     * @return bool
     */
    public function open($save_path, $session_name): bool
    {
        return true;
    }

    /**
     * On read session
     * 
     * @param string|false
     */
    public function read($session_id): string
    {
        $session = self::getTable()
            ->select(['data'])
            ->where('id', $session_id)
            ->fetchOne();

        if ($session) {
            return base64_decode($session->data);
        }

        return '';
    }

    /**
     * On write session data
     * 
     * @param string $session_id Session Id
     * @param string $session_data Data to write to session
     * 
     * @return bool
     */
    public function write($session_id, $session_data): bool
    {
        self::getTable()
            ->replace([
                'id' => $session_id,
                'user_id' => Auth::id(),
                'last_update' => date('Y-m-d H:i:s'),
                'data' => base64_encode($session_data),
                'created_at' => getnow()
            ]);

        return true;
    }

    /**
     * Initialize Session manager
     *
     * @return void
     */
    public function init()
    {
        return $this->up();
    }

    /**
     * Delete all session related to $user_id
     *
     * @param mixed $user_id
     * @return void
     */
    public static function discardAllForUser($user_id)
    {
        self::getTable()->delete()
            ->where('user_id', $user_id)
            ->fulfil();
    }

    /**
     * Returns if session manager is ready
     *
     * @return boolean
     */
    public static function isReady()
    {
        return static::$can_run;
    }

    /**
     * Start session manager
     *
     * @return void
     */
    public static function initialize()
    {
        $config = App::getConfig('app');
        $session = $config['session'] ?? 'default';

        if ($session === 'database') {
            return static::$can_run = true;
        }

        static::$can_run = false;
    }

    /**
     * Get session database table
     *
     * @return DBTable
     */
    private static function getTable(): DBTable
    {
        return new DBTable(
            self::$schema_name,
            self::getConnection()
        );
    }

    /**
     * Get Database connection
     *
     * @return DBConnection
     */
    private static function getConnection(): DBConnection
    {
        $config = App::getConfig('app');
        $connection_name = $config['session_connection'] ?? null;
        return DBConnection::connection($connection_name);
    }

    /**
     * Build session schema
     * 
     * For session handler
     */
    private function up()
    {
        self::getTable()->build(function ($table) {
            $table->field('id')->varchar()->primary();
            $table->field('data')->text();
            $table->field('user_id')->int()->nullable();
            $table->field('last_update')->datetime();
        });
    }
}
