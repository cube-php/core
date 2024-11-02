<?php

namespace Cube\Misc;

use Cube\Misc\Collection;
use Cube\Misc\Input;

class Inputs extends Collection
{

    /**
     * Inputs constructor
     * 
     */
    public function __construct($content)
    {
        is_string($content) ? parse_str($content, $data) : $data = $content;
        foreach ($data as $index => $value) {
            $this->set($index, $value);
        }
    }

    /**
     * Return key
     * 
     * @param string $key Key to return
     * 
     * @return \Cube\Misc\Input
     */
    public function get($key)
    {
        $vars = explode('.', trim($key));
        $value = $this->all();

        foreach ($vars as $var) {
            $value = $value[$var] ?? null;
        }

        if (!is_array($value)) return new Input($value, $var);
        return $value;
    }
}
