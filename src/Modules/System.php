<?php

namespace Cube\Modules;

use Cube\App\App;
use Cube\App\Directory;
use Cube\Exceptions\AppException;
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
        $this->_system_file_path = concat(
            App::getPath(Directory::PATH_ROOT),
            DIRECTORY_SEPARATOR,
            'core',
            DIRECTORY_SEPARATOR,
            'app.php'
        );
    }

    /**
     * Init system commands
     *
     * @return string
     */
    public function init()
    {
        try {
            $this->initSystemsUtilities();
            $this->initCustomCommands();
        } catch (Exception $e) {

            throw new AppException("Unable to intialize system \n" . $e->getMessage(), true);
        }
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
