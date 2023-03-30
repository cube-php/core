<?php

namespace Cube\App;

use Cube\Http\Env;
use Cube\Http\Request;
use Cube\Http\Session;
use Cube\Misc\Components;
use Cube\Misc\EventManager;
use Cube\Modules\SessionManager;
use Cube\Router\RouteCollection;
use Throwable;

class App
{
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
     * App event
     * 
     * On app initialization
     * 
     * @var string
     */
    const EVENT_INITIALIZED = 'onAppInit';

    /**
     * On route match found event
     * 
     * Events when route match is found
     * 
     * @var string
     */
    const EVENT_ROUTE_MATCH_FOUND = 'onRouteMatchFound';

    /**
     * Event when no route match is found
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
     * Holds and generate all app paths
     *
     * @var Path
     */
    private static $directory;

    /**
     * Instance
     *
     * @var self
     */
    private static $instance;

    /**
     * Caches
     *
     * @var array
     */
    private static $caches = array();

    /**
     * Check if app is running via cli
     * 
     * @var boolean
     */
    private bool $is_terminal = false;

    /**
     * Class constructor
     *
     * @param string $dir
     */
    public function __construct(string $dir)
    {
        self::$directory = new Directory($dir);
        self::$instance = $this;
    }

    /**
     * Destruct
     */
    public function __destruct()
    {
        if ($this->is_terminal) {
            return;
        }

        EventManager::dispatchEvent(
            self::EVENT_RUNNING,
            $this
        );
    }

    /**
     * Intialize app
     *
     * @return void
     */
    public function init()
    {
        $this->loadConfig();
        $this->setTimezone();
        $this->initHelpers();
        $this->initSessions();
        $this->loadComponent();
        $this->loadEvents();

        EventManager::dispatchEvent(self::EVENT_INITIALIZED, $this);
    }

    /**
     * Get configured directory
     *
     * @return Directory
     */
    public function directory()
    {
        return self::$directory;
    }

    /**
     * Run the app
     *
     * @return void
     */
    public function run()
    {
        $this->init();

        try {
            $this->initRoutes();
        } catch (Throwable $e) {
            if (App::isDevelopment()) {
                throw $e;
            }

            EventManager::dispatchEvent(self::EVENT_APP_ON_CRASH, $e);
        }
    }

    /**
     * Load config
     *
     * @return void
     */
    private function loadConfig()
    {
        $this->requireDirectoryFiles(
            $this->directory()->get(Directory::PATH_CONFIG)
        );
    }

    /**
     * Load components
     *
     * @return void
     */
    private function loadComponent()
    {
        $components = self::getConfig('components');

        if (!is_array($components)) {
            return;
        }

        array_walk($components, function ($fn, $name) {
            Components::register($name, $fn);
        });
    }

    /**
     * Load events
     *
     * @return void
     */
    private function loadEvents()
    {
        $events = self::getConfig('events');

        if (!is_array($events)) {
            return;
        }

        array_walk($events, function ($callbacks, $name) {
            array_walk($callbacks, function ($callback) use ($name) {
                EventManager::on($name, $callback);
            });
        });
    }

    /**
     * require files from directory
     *
     * @param string $dirname
     * @param boolean $cache
     * @return void
     */
    private function requireDirectoryFiles($dirname, bool $cache = true)
    {

        $files = scandir($dirname);

        array_walk($files, function ($name) use ($dirname, $cache) {
            $path = $dirname . '/' . $name;
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            if ($ext !== 'php') {
                return;
            }

            $dirname_vars = explode(DIRECTORY_SEPARATOR, $dirname);
            $cache_name = array_pop($dirname_vars);

            $name_vars = explode('.', $name);
            $fname = $name_vars[0];

            if ($cache) {
                return self::$caches[$cache_name][$fname] = require_once $path;
            }

            require_once $path;
        });
    }

    /**
     * Set app's timezone
     *
     * @return void
     */
    private function setTimezone()
    {
        $timezone = self::getConfig('app', 'timezone');

        if ($timezone) {
            date_default_timezone_set($timezone);
        }
    }

    /**
     * Initialize helpers
     *
     * @return bool
     */
    public function initHelpers()
    {
        $custom_helpers_dir = concat(
            $this->getPath(Directory::PATH_APP),
            '/helpers'
        );

        $this->requireDirectoryFiles($custom_helpers_dir);
    }

    /**
     * Set if app is running via terminal
     *
     * @return boolean
     */
    public function isRunningViaTerminal(): bool
    {
        return $this->is_terminal;
    }

    /**
     * Set if app is running via terminal
     *
     * @param boolean $status
     * @return boolean
     */
    public function setIsRunningViaTerminal(bool $status = true)
    {
        $this->is_terminal = $status;
    }

    /**
     * Force https
     *
     * @return Response|bool
     */
    private function checkForcedHttps()
    {
        $https = 'https';
        $config = self::getConfig('app');
        $force_https = $config['force_https'] ?? false;

        if (!$force_https) {
            return false;
        }

        $request = Request::getRunningInstance();
        $url_scheme = strtolower($request->url()->getScheme());

        if ($url_scheme === $https) {
            return false;
        }

        $secure_uri = concat($https, '://', $request->url()->getFullUrl(false));
        return redirect($secure_uri, [], true);
    }

    /**
     * Initialize routes
     *
     * @return void
     */
    private function initRoutes()
    {
        $this->checkForcedHttps();
        $this->requireDirectoryFiles(
            $this->directory()->get(Directory::PATH_ROUTES)
        );

        $routes = new RouteCollection();
        $routes->build();
    }

    /**
     * Initialize session
     *
     * @return void
     */
    private function initSessions()
    {
        if (!$this->is_terminal) {
            SessionManager::initialize();
        }

        Session::createInstance();
    }

    /**
     * Get app environment
     *
     * @return string
     */
    public static function environment()
    {
        return env('app_env');
    }

    /**
     * Get config
     *
     * @param string $name
     * @param mixed $value
     * @return array|string
     */
    public static function getConfig(string $name, $value = null)
    {
        if (!isset(self::$caches['config'])) {
            self::getRunningInstance()->loadConfig();
        }

        $config = self::$caches['config'];

        if (!isset($config[$name])) {
            return null;
        }

        $data = $config[$name];

        if (!is_array($data)) {
            return null;
        }

        if ($value && !isset($data[$value])) {
            return null;
        }

        return $value ? $data[$value] : $data;
    }

    /**
     * Get path
     *
     * @param string $pathname
     * @return string|null
     */
    public static function getPath($pathname)
    {
        return self::getRunningInstance()
            ->directory()
            ->get($pathname);
    }

    /**
     * Return value if app is in production env
     *
     * @return boolean
     */
    public static function isProduction()
    {
        $config = strtolower(Env::getMain('app_env'));
        return in_array($config, ['prod', 'production']);
    }

    /**
     * Return value if app is in development env
     *
     * @return boolean
     */
    public static function isDevelopment()
    {
        return !self::isProduction();
    }

    /**
     * Get running app instance
     *
     * @return self|null
     */
    public static function getRunningInstance(): ?self
    {
        return self::$instance;
    }
}
