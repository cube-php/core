<?php

namespace Cube\Misc;

class ModelCollection extends Collection
{
    /**
     * Create new instance
     *
     * @param array $array
     * @return self
     */
    public static function new(array $array): self
    {
        return new self($array);
    }
}
