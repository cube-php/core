<?php

namespace Cube\Misc;

use Cube\App\App;
use Cube\Helpers\Cli\Cli;

/**
 * Cube plugins/packages manager class
 * Currently in Beta
 */
class PackageManager
{
    /**
     * Method to call when new package is installed
     *
     * @param Composer\Script\Event $event
     * @return void
     */
    public static function onPackageInstall($event)
    {
        $dir = $event->getComposer()->getConfig()->get('vendor-dir');
        require_once $dir . '/autoload.php';

        $base_dir = $dir . '/..';
        require_once $base_dir . '/core/app.php';
        $files = scandir($dir);

        array_walk($files, function ($file) use ($dir) {

            if (!str_starts_with($file, 'cube-php')) {
                return;
            }

            $full_dir = $dir . DIRECTORY_SEPARATOR . $file;
            self::discoverPackages($full_dir);
        });
    }

    /**
     * Discover installed packages
     *
     * @param string $packages_path
     * @return void
     */
    protected static function discoverPackages(string $packages_path)
    {
        $files = scandir($packages_path);
        array_walk($files, function ($file) use ($packages_path) {

            if (in_array($file, ['.', '..'])) {
                return;
            }

            self::discoverPackage($packages_path, $file);
        });
    }

    /**
     * Check for new package
     *
     * @param string $parent
     * @param string $file
     * @return void
     */
    protected static function discoverPackage(string $parent, string $file)
    {
        $composer_json = $parent . DIRECTORY_SEPARATOR . $file . DIRECTORY_SEPARATOR . 'composer.json';

        if (!file_exists($composer_json)) {
            return;
        }

        $content = file_get_contents($composer_json);
        $json_content = json_decode($content, true);
        $extra_command = $json_content['extra']['commands'] ?? null;

        if (!$extra_command) {
            return;
        }

        Cli::addExtraPackageCommand($extra_command);
    }
}
