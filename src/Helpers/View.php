<?php

namespace Cube\Helpers;

use Cube\Http\Env;
use Cube\App;
use Cube\Misc\EventManager;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

class View
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
        'route'
    );

    /**
     * View constructor
     *
     * @param string $path
     */
    public function __construct(string $path = '')
    {
        $this->_config = App::getConfigByName('view');
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
    public function engageFilters(array $filters) {
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
    public function engageFunctions(array $functions) {
        foreach ($functions as $function) {
            $fn = new TwigFunction($function, $function, array(
                'is_safe' => array('html')
            ));

            $this->_twig->addFunction($fn);
        }
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

        if(isset($this->_config['cache']) && $this->_config['cache']) {
            $view_options['cache'] = VIEW_PATH . DS . '.cache';
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