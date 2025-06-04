<?php

namespace Cube\Helpers\Logger;

use Cube\App\App;
use Cube\App\Directory;
use Cube\Interfaces\LoggerInterface;
use Cube\Misc\File;

class Logger implements LoggerInterface
{

    private $handlers = [];

    /**
     * File
     *
     * @var File
     */
    private $_file;

    public function __construct(string $suffix = '')
    {
        $curdate = date('d_m_Y');
        $filename = $suffix ? concat($curdate, '_', $suffix) : $curdate;

        $path = concat(
            App::getRunningInstance()->getPath(Directory::PATH_ROOT),
            '/logs'
        );

        $filename = File::joinPath($path, $filename . '.log');
        $this->_file = new File($filename, true);
    }

    public function __destruct()
    {
        $this->_file->close();
    }

    /**
     * Add callback for on log action
     *
     * @param callable $func
     * @return self
     */
    public function onLog(callable $func)
    {
        $this->handlers[] = $func;
        return $this;
    }

    /**
     * Get
     *
     * @return contents
     */
    public function get()
    {
        return $this->_file->getContent();
    }

    /**
     * Write a log
     *
     * @param string $data
     * @return bool
     */
    public function set(string $data): bool
    {
        $content_prefix = date('[g:i:sa]');
        $content = $content_prefix . ' ' . $data . PHP_EOL;

        $this->_file->write($content);
        $this->executeHandlers($data);
        return true;
    }

    /**
     * Static method to log content
     *
     * @param string $content
     * @param string $suffix
     * @return bool
     */
    public static function log(string $content, string $suffix = '')
    {
        $logger = new self($suffix);
        return $logger->set($content);
    }

    private function executeHandlers($content)
    {
        foreach ($this->handlers as $handler) {
            $handler($content);
        }

        return true;
    }
}
