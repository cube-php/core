<?php

namespace Cube\Http\Model;

/**
 * Keys supported by a model relation declaration.
 */
final class RelationConfig
{
    public const Type = 'type';
    public const Model = 'model';
    public const LocalKey = 'local_key';
    public const ForeignKey = 'foreign_key';

    private function __construct() {}
}
