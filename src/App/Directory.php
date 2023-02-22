<?php

namespace Cube\App;

class Directory
{
    public const PATH_ROOT    = 'ROOT';
    public const PATH_APP     = 'APP';
    public const PATH_ROUTES  = 'ROUTES';
    public const PATH_VIEWS   = 'VIEWS';
    public const PATH_WEBROOT = 'WEBROOT';
    public const PATH_LOGS    = 'LOGS';
    public const PATH_CONFIG  = 'CONFIG';
    public const PATH_STORAGE = 'STORAGE';
    public const PATH_HELPERS = 'HELPERS';

    private $base_path = null;

    /**
     * All path paths
     *
     * @var array
     */
    private $paths = array();

    public function __construct($dir)
    {
        $this->base_path = $dir;
        $this->build();
    }

    /**
     * Get path
     *
     * @param string $path
     * @return string|null
     */
    public function get(string $path): ?string
    {
        return $this->paths[$path] ?? null;
    }

    /**
     * Build paths
     *
     * @return void
     */
    private function build()
    {
        $ds = DIRECTORY_SEPARATOR;

        $root = $this->base_path;
        $app_path = concat($root, $ds, 'app');
        $webroot = concat($root, $ds, 'webroot');

        $this->paths = array(
            self::PATH_ROOT => $root,
            self::PATH_APP => $app_path,
            self::PATH_ROUTES => concat($root, $ds, 'routes'),
            self::PATH_VIEWS => concat($app_path, $ds, 'views'),
            self::PATH_WEBROOT => $webroot,
            self::PATH_LOGS => concat($root, $ds, 'logs'),
            self::PATH_CONFIG => concat($root, $ds, 'config'),
            self::PATH_STORAGE => concat($webroot, $ds, 'assets')
        );
    }
}