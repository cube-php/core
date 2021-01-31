<?php

namespace Cube\Modules;

use Exception;
use Cube\Tools\Auth;
use Cube\Helpers\Cli\Cli;
use Cube\Modules\SessionManager;

class System
{
    /**
     * System file path
     *
     * @var string
     */
    private $_system_file_path;

    /**
     * Session
     *
     * @var Session
     */
    private $_session;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_session = new SessionManager();
        $this->_system_file_path = APP_PATH . DS . 'core' . DS . 'system.php';
    }

    /**
     * Init system commands
     *
     * @return string
     */
    public function init()
    {
        try {
            Cli::respond('Executing system logic');
            $this->initSystemsUtilities();
            Cli::respondSuccess('System login completed');
            Cli::respond('Executing custom logic');
            $this->initCustomCommands();
            Cli::respondSuccess('Custom logic completed');
        } catch (Exception $e) {

            Cli::respondError("Unable to intialize system \n" . $e->getMessage(), true);
        }
            
    }

    /**
     * Load up all schemas
     *
     * @return void
     */
    public function schemas()
    {
        $tables = DB::tables();
        if(!$tables) {
            Cli::respond('No schemas created yet', true);
        }

        Cli::respond('FETCHING DATABASE SCHEMAS...');
        Cli::respond('');

        foreach($tables as $table) {
            Cli::respond($table);
        }
    }

    /**
     * System function to drop table
     *
     * @param string $name Table name
     * @return void
     */
    public function schemaDropTable($name)
    {
        Cli::respond('Dropping table -> ' . $name);
        DB::table($name)->drop();
        Cli::respondSuccess('Table "' . $name . '" dropped successfully');
    }

    /**
     * Execute custom logic code
     *
     * @return mixed
     */
    private function initCustomCommands()
    {
        return require_once $this->_system_file_path;
    }

    /**
     * Initialize cubes core utilities
     *
     * @return boolean
     */
    private function initSystemsUtilities()
    {
        Auth::up();
        $this->_session->init();
    }
}