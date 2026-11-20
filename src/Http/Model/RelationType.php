<?php

namespace Cube\Http\Model;

/**
 * Cardinality of a model relation.
 */
enum RelationType: string
{
    case One = 'one';
    case Many = 'many';
}
