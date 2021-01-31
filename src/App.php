<?php

namespace Cube;

use Exception;
use Cube\Http\Session;
use Cube\Http\Request;
use Cube\Http\Response;

use Cube\Router\RouteCollection;

use Cube\Misc\EventManager;
use Cube\Misc\Components;
use Cube\Exceptions\AppException;

class App
{
    const INSTANCE_CONFIGURATIONS = 'config';
    const INSTANCE_ROUTES         = 'routers';

    /**
     * App is development
     * 
     * @var int
     */
    const APP_MODE_DEVELOPMENT = 101;

    /**
     * 
     * @var int
     */
    const APP_MODE_PRODUCTION = 102;

    /**
     * App event
     * 
     * Before app runs
     * 
     * @var string
     */
    const EVENT_BEFORE_RUN = 'onBeforeAppStart';

    /**
     * App event
     * 
     * Before app runs
     * 
     * @var string
     */
    const EVENT_RUNNING = 'onAppRunning';

    /**
     * On route match found event
     * 
     * Events when route match is found
     * 
     * @var string
     */
    const EVENT_ROUTE_MATCH_FOUND = 'onRouteMatchFound';

    /**
     * Event when not route match is found
     * 
     * @var string
     */
    const EVENT_ROUTE_NO_MATCH_FOUND = 'onRouteNoMatchFound';

    /**
     * Event when app is in development mode
     * 
     * @var string
     */
    const EVENT_APP_ON_DEVELOPMENT  = 'onAppDevelopment';
    
    /**
     * Event when app is in production mode
     * 
     * @var string
     */
    const EVENT_APP_ON_PRODUCTION  = 'onAppProduction';

    /**
     * Event when app crashes
     * 
     * @var string
     */
    const EVENT_APP_ON_CRASH       = 'onAppCrash';

    /**
     * Loaded configurations
     * 
     * @var array
     */
    private static $config = array();

    /**
     * Request
     * 
     * @var \Cube\Http\Request
     */
    private $_request;

    /**
     * Class constructor
     * 
     * @return void
     */
    public function __construct()
    {
        
        $this->_request = new Request;

        EventManager::dispatchEvent(self::EVENT_BEFORE_RUN, $this);
    }

    /**
     * Class destruct
     * 
     * @return void
     */
    public function __destruct()
    {
        EventManager::dispatchEvent(self::EVENT_RUNNING, $this);
    }

    /**
     * Get configuration
     * 
     * @param string $name Configuration name
     * 
     * @return array
     */
    public static function getConfigByName($name)
    {
        return static::$config[self::INSTANCE_CONFIGURATIONS][$name] ?? null;
    }

    /**
     * Load configuration by file
     * This will load configuration even when app is has not been started
     *
     * @param string $name Config name
     * @return array
     */
    public static function getConfigByFile($name)
    {
        if(isset(static::$config[self::INSTANCE_CONFIGURATIONS][$name])) {
            return static::$config[self::INSTANCE_CONFIGURATIONS][$name];
        }

        $path = CONFIG_PATH . DS . $name . '.php';
        $config = require_once $path;
        
        static::$config[self::INSTANCE_CONFIGURATIONS][$name] = $config;
        return $config;
    }

    /**
     * Returns specified enviroment
     * 
     * @return string
     */
    public static function getEnviroment()
    {
        $config = static::getConfigByName('app');
        return $config['app_mode'];
    }

    /**
     * Returns true if is in development mode
     * 
     * @return boolean
     */
    public static function isDevelopment()
    {
        return !static::isProduction();
    }
    
    /**
     * Returns true if is in production mode
     * 
     * @return boolean
     */
    public static function isProduction()
    {
        $config = static::getConfigByName('app');
        return ($config['app_mode'] === self::APP_MODE_PRODUCTION);
    }

    /**
     * Register paths
     *
     * @return bool
     */
    public static function registerPath()
    {
        if(!defined('DS')) {
            define('DS', DIRECTORY_SEPARATOR);
        }

        #Root directory
        define('APP_PATH', __DIR__ . DS . '..' . DS . '..');

        #App directory
        define('CORE_APP_PATH', APP_PATH . DS . 'main');

        #Main app directory
        define('MAIN_APP_PATH', APP_PATH . DS . 'app');

        #App's routes directory
        define('APP_ROUTES_PATH', APP_PATH . DS . 'routes');

        # App view directory
        define('VIEW_PATH', MAIN_APP_PATH . DS . 'views');

        #App webroot path
        define('APP_WEBROOT_PATH', APP_PATH . DS . 'webroot');

        #App logs path
        define('APP_LOGS_PATH', APP_PATH . DS . 'logs');

        # Configuration directory
        define('CONFIG_PATH', APP_PATH . DS . 'config' . DS);

        # Storage path
        define('APP_STORAGE', APP_PATH . DS . 'webroot' .  DS . 'assets');

        #Controllers Path
        define('APP_CONTROLLERS_PATH', MAIN_APP_PATH . DS . 'Controllers');

        #Events Path
        define('APP_EVENTS_PATH', MAIN_APP_PATH . DS . 'Events');

        #Models Path
        define('APP_MODELS_PATH', MAIN_APP_PATH . DS . 'Models');

        #Providers Path
        define('APP_PROVIDERS_PATH', MAIN_APP_PATH . DS . 'Providers');

        #Middlewares Path
        define('APP_MIDDLEWARES_PATH', MAIN_APP_PATH . DS . 'Middlewares');

        #Exceptions Path
        define('APP_EXCEPTIONS_PATH', MAIN_APP_PATH . DS . 'Exceptions');

        #Helpers Path
        define('APP_HELPERS_PATH', MAIN_APP_PATH . DS . 'helpers');

        #Migration Path
        define('APP_MIGRATIONS_PATH', MAIN_APP_PATH . DS . 'Migrations');

        #Providers Path
        define('APP_PUBLIC_STORAGE_PATH', APP_STORAGE . DS . 'storage');

        return true;
    }

    /**
     * Start the app
     * 
     * @return void
     */
    public function run()
    {
        $this->init();
        $this->setTimezone();
        $this->initSessions();

        if(!self::isProduction()) {
            $this->boot();
            return;
        }

        try {
            $this->boot();

        } catch(Exception $e) {
            EventManager::dispatchEvent(self::EVENT_APP_ON_CRASH, $e);
        }
    }

    /**
     * Load up app core components without initializing routes
     *
     * @return void
     */
    public function init(): void
    {
        $this->loadConfig();
        $this->loadComponents();
        $this->loadEvents();
        $this->configure();
    }

    /**
     * Boot up app
     *
     * @return void
     */
    private function boot()
    {
        $this->configureHttps();
        $this->initRoutes();
    }

    /**
     * App mode checker
     * 
     * @param int $mode Registered mode
     * 
     * @return void
     */
    private function appModeChecker($mode = null)
    {

        #If no app mode, let's set it default to development
        $mode = $mode ?? self::APP_MODE_DEVELOPMENT;

        switch($mode)
        {
            #On development mode
            case self::APP_MODE_DEVELOPMENT:
                #Yea we want this on
                ini_set('display_errors', 'On');

                #Set error reporting
                error_reporting(E_ALL);

                #Let's initiate other registered events
                EventManager::dispatchEvent(self::EVENT_APP_ON_DEVELOPMENT, $this);
            break;

            #On production mode
            case self::APP_MODE_PRODUCTION:
                #Yea we want this off
                ini_set('display_errors', 'Off');
                
                #No error reporting
                error_reporting(0);

                #Let's initiate other registered events
                EventManager::dispatchEvent(self::APP_MODE_PRODUCTION, $this);
            break;
        }
    }
    
    /**
     * Configure Application
     * 
     * @return
     */
    private function configure()
    {
        #Load configuration
        $config = static::getConfigByName('app');

        #If configuration, kill the app
        if(!$config) {
            self::kill('Unable to load app configuration file');
        }

        #Check app mode
        $this->appModeChecker($config['app_mode'] ?? null);
    }

    /**
     * Configure force HTTPs
     * 
     * 
     * @return void;
     */
    private function configureHttps()
    {
        $redirect = static::getConfigByName('app')['force_https'] ?? false;
        $scheme = substr(strtolower($this->_request->url()->getScheme()), 0, 5);
        $secure_scheme = 'https';

        if($scheme == $secure_scheme) return true;

        #If user has set force_https to false
        if(!$redirect) return;

        $url  = $this->_request->url()->getUrlWithoutScheme();
        $rdr_url = $secure_scheme . '://' . $url;
        
        return (Response::getInstance())
            ->redirect($rdr_url, [], true);
    }

    /**
     * Make the app listen to routes
     * 
     * @return void
     */
    private function initRoutes()
    {
        #Load up assigned routes
        $this->loadDirFiles(APP_ROUTES_PATH, false);

        #Build routes
        $routes = new RouteCollection();
        $routes->build();
    }   

    /**
     * Initialize sessions
     * 
     * @return void
     */
    private function initSessions()
    {
        return Session::createInstance();
    }

    /**
     * Kill the app process
     *
     * @param string $reason Reason to kill the app
     *  
     * @return void
     */
    private static function kill($reason)
    {
        die((string) $reason);
    }

    /**
     * Load assigned components to context
     *
     * @return self
     */
    private function loadComponents()
    {
        $components = static::getConfigByName('components');
        
        if(!$components || !count($components)) {
            return;
        }

        foreach ($components as $name => $value) {
            Components::register($name, $value);
        }

        return $this;
    }
    

    /**
     * Loading configuration files
     * 
     * @return void
     */
    private function loadConfig()
    {
        $this->loadDirFiles(CONFIG_PATH);
    }

    /**
     * Load and require all files from specified directory
     *
     * @param string $dir_path Directory to load php files from
     * @param boolean $save_to_instance Save included file to app's config instances
     * @return void
     */
    private function loadDirFiles($dir_path, bool $save_to_instance = true)
    {
        #Oh dots
        $dots = array('.', '..');

        $raw_filelist = scandir($dir_path);
        $files = array_diff($raw_filelist, $dots);
        
        if(!count($files)) {
            return null;
        }

        foreach($files as $file) {
            $file_path = $dir_path . DS . $file;
            $name_vars = explode('.', $file);
            $extension = strtolower($ext = array_slice($name_vars, -1)[0]);
            $name = implode('.', $left_vars = array_splice($name_vars, 0, -1));

            if(!($extension === 'php' && !isset(static::$config[$name]))) {
                continue;
            }

            if($save_to_instance) {
                self::$config[self::INSTANCE_CONFIGURATIONS][$name] = require_once($file_path);
                continue;
            }

            require_once($file_path);
        }
    }

    /**
     * Load/Register Assigned events
     *
     * @return void
     */
    private function loadEvents()
    {
        $events = App::getConfigByFile('events');
        
        foreach($events as $handler => $handles) {
            array_map(function($handle) use ($handler) {
                EventManager::on($handler, $handle);
            }, $handles);
        }
    }

    /**
     * Set App timezone
     *
     * @return void
     */
    private function setTimezone() : void
    {
        $timezone = self::getConfigByName('app')['time_zone'] ?? null;

        if(!$timezone) {
            throw new AppException('Set App Timezone');
        }

        date_default_timezone_set($timezone);
    }
}