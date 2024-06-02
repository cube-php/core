<?php

namespace Cube\View;

use Cube\Http\Env;
use Cube\App\App;
use Cube\App\Directory;
use Cube\Misc\EventManager;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

class ViewRenderer
{
    const EVENT_LOADED = 'viewLoaded';

    /**
     * Twig
     *
     * @var Environment
     */
    private $_twig;

    /**
     * View config
     *
     * @var array
     */
    private $_config = array();

    /**
     * System functions
     *
     * @var array
     */
    private $_system_functions = array(
        'css',
        'jscript',
        'env',
        'url',
        'asset',
        'csrf_token',
        'csrf_form',
        'route',
        'component'
    );

    /**
     * View constructor
     *
     * @param string $path
     */
    public function __construct(string $path = '')
    {
        $this->_config = App::getConfig('view');
        $this->setBasePath($path);
    }

    /**
     * Class destructor
     */
    public function __destruct()
    {
        EventManager::dispatchEvent(self::EVENT_LOADED);
    }

    /**
     * Engage filters
     *
     * @param array $filters
     * @return void
     */
    public function engageFilters(array $filters)
    {
        foreach ($filters as $filter => $closure) {
            $fn = new TwigFilter($filter, $closure, array(
                'is_safe' => array('html')
            ));

            $this->_twig->addFilter($fn);
        }
    }

    /**
     * Engage functions
     *
     * @param array $functions
     * @return void
     */
    public function engageFunctions(array $functions)
    {
        every($functions, function ($callable, $key) {
            $has_value = !is_numeric($key);
            $name = $has_value ? $key : $callable;

            $fn = new TwigFunction($name, $callable, array(
                'is_safe' => array('html')
            ));

            $this->_twig->addFunction($fn);
        });
    }

    /**
     * Set view base path
     *
     * @param string $path
     * @return void
     */
    public function setBasePath(string $path)
    {
        $loader = new FilesystemLoader($path);
        $view_options = array(
            'strict_variables' => App::isDevelopment(),
        );

        $should_cache = $this->_config['cache'] ?? false;
        $cache_dir = $this->_config['cache_dir'] ?? null;

        if ($should_cache) {

            $app_cache_dir = App::getRunningInstance()->getPath(
                Directory::PATH_CACHE
            );

            $cache_path = $app_cache_dir . '/' . $cache_dir;

            if ($cache_path && !is_dir($cache_path)) {
                mkdir($cache_path, 0775, true);
            }

            if ($cache_path && $should_cache) {
                $view_options['cache'] = $cache_path;
            }
        }


        $this->_twig = new Environment($loader, $view_options);
        $this->_twig->addGlobal('env', Env::all());
        $this->engageFunctions($this->_system_functions);

        $custom_functions = $this->_config['functions'] ?? [];
        $custom_filters = $this->_config['filters'] ?? [];

        $this->engageFunctions($custom_functions);
        $this->engageFilters($custom_filters);
    }

    /**
     * Render all views
     *
     * @param string $path Path to file to render
     * @param array $datas Data to send to view
     * @return string
     */
    public function render(string $path, array $datas = [])
    {
        return $this->_twig->render(static::parsePathName($path), $datas);
    }

    /**
     * Re-parser name
     *
     * @param string $name
     * @return string
     */
    private static function parsePathName($name)
    {
        $name_vars = explode('.', $name);
        $new_name = implode('/', $name_vars);
        return $new_name . '.twig';
    }
}
