<?php

namespace Cube\App;

use Cube\Helpers\Response\ResponseEmitter;
use Cube\Http\Env;
use Cube\Http\Request;
use Cube\Http\Response;
use Cube\Interfaces\RequestInterface;
use Cube\Misc\Components;
use Cube\Misc\EventManager;
use Cube\Modules\Db\DBConnector;
use Cube\Router\ControllerRoutesLoader;
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
     * @deprecated v0.1.23
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
    const EVENT_APP_ON_DEVELOPMENT = 'onAppDevelopment';

    /**
     * Event when app is in production mode
     * 
     * @var string
     */
    const EVENT_APP_ON_PRODUCTION = 'onAppProduction';

    /**
     * Event when app crashes
     * 
     * @var string
     */
    const EVENT_APP_ON_CRASH = 'onAppCrash';

    /**
     * Caches
     *
     * @var array
     */
    private array $caches = array();

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
        app()->singleton(
            Directory::class,
            fn() => new Directory($dir)
        );

        app()->singleton(
            App::class,
            fn() => $this
        );

        app()->singleton(
            AppExceptionsHandler::class,
            fn() => new AppExceptionsHandler()
        );

        $this->init();
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
        $this->initExceptionHandlers();
        $this->setTimezone();
        $this->initHelpers();
        $this->loadComponent();
        $this->loadEvents();
        $this->initRoutes();

        EventManager::dispatchEvent(self::EVENT_INITIALIZED, $this);
    }

    /**
     * Get configured directory
     *
     * @return Directory
     */
    public function directory(): Directory
    {
        return app(Directory::class);
    }

    /**
     * Run the app
     *
     * @return void
     */
    public function run(?RequestInterface $request = null): mixed
    {
        $runtime = EventManager::dispatchEvent(self::EVENT_BEFORE_RUN, $this);

        if ($runtime) {
            return $runtime($this, $request);
        }

        return $this->runApp($request);
    }

    /**
     * Run app
     *
     * @param RequestInterface|null $request
     * @return void
     */
    public function runApp(?RequestInterface $request = null)
    {
        $response = $this->handle($request);
        $emitter = new ResponseEmitter($response);
        $emitter->emit();
    }

    /**
     * Handle request
     *
     * @param Request|null $request
     * @return Response
     */
    public function handle(?Request $request = null): Response
    {
        app()->scoped(
            Request::class,
            fn() => $request ?: Request::createHttpRequestFromGlobals()
        );

        try {
            return (
                new RouteCollection(app(Request::class))
            )->dispatch();
        } catch (Throwable $e) {
            return app(AppExceptionsHandler::class)->handle($e);
        }
    }

    /**
     * Terminate app
     *
     * @return void
     */
    public function terminate()
    {
        session_write_close();
        DBConnector::resetRequestState();
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
     * @param boolean $should_cache
     * @return void
     */
    private function requireDirectoryFiles($dirname, bool $should_cache = true)
    {

        $files = scandir($dirname);

        array_walk($files, function ($name) use ($dirname, $should_cache) {
            $path = $dirname . '/' . $name;
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            if ($ext !== 'php') {
                return;
            }

            $dirname_vars = explode(DIRECTORY_SEPARATOR, $dirname);
            $cache_name = array_pop($dirname_vars);

            $name_vars = explode('.', $name);
            $fname = $name_vars[0];
            $content = require_once $path;

            if ($should_cache && !isset($this->caches[$cache_name][$fname])) {
                return $this->caches[$cache_name][$fname] = $content;
            }
        });
    }

    /**
     * Set app's timezone
     *
     * @return void
     */
    private function setTimezone()
    {
        $timezone = self::getConfig('app.timezone');

        if ($timezone) {
            date_default_timezone_set($timezone);
        }
    }

    /**
     * Initialize helpers
     *
     * @return bool
     */
    private function initHelpers()
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
     * Init exception handler
     *
     * @return void
     */
    private function initExceptionHandlers()
    {
        $exception = static::getConfig('exception');

        if (is_callable($exception)) {
            $exception(
                app(AppExceptionsHandler::class)
            );
        }
    }

    /**
     * Initialize routes
     *
     * @return void
     */
    private function initRoutes()
    {
        $this->requireDirectoryFiles(
            $this->directory()->get(Directory::PATH_ROUTES)
        );

        ControllerRoutesLoader::load();
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
    public static function getConfig(string $name, $default = null)
    {
        $instance = app(self::class);
        if (!isset($instance->caches['config'])) {
            $instance->loadConfig();
        }

        $config = $instance->caches['config'];
        $name_vars = explode('.', $name);
        $config_name = $name_vars[0];

        if (!isset($config[$config_name])) {
            return null;
        }

        $data = $config[$config_name];
        $children = array_slice($name_vars, 1);

        foreach ($children as $key) {
            if (!is_array($data) || !isset($data[$key])) {
                return $default;
            }

            $data = $data[$key];
        }

        return $data;
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
        $config = strtolower((string) Env::getMain('app_env'));
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
        return app(App::class);
    }
}
