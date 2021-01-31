<?php

namespace Cube\Helpers\Logger;

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

    public function __construct()
    {
        $curdate = date('d_m_Y');
        $filename = File::joinPath(APP_LOGS_PATH, "{$curdate}.log");
        $this->_file = new File($filename, true);
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
    public function set(string $data) : bool
    {
        $content_prefix = date('[g:i:sa]');
        $content = $content_prefix . ' ' . $data . PHP_EOL;

        $this->_file->write($content);
        $this->executeHandlers($data);
        return true;
    }

    private function executeHandlers($content)
    {
        foreach($this->handlers as $handler) {
            $handler($content);
        }

        return true;
    }
}