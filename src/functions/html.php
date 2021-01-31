<?php

/**
 * HTML functions should go here
 */

/**
 * Create HTML Element
 *
 * @param string $element_name Element name to create
 * @param array|null $attributes Element attributes
 * @param mixed $innerhtml Element content
 * @return string Generated htmt
 */
function h($element_name, ?array $attributes = [], $innerhtml = null) {
    $self_closing = array(
        'area',
        'base',
        'br',
        'col',
        'command',
        'embed',
        'hr',
        'img',
        'input',
        'keygen',
        'link',
        'menuitem',
        'meta',
        'param',
        'source',
        'track',
        'wbr'
    );

    $element_name = strtolower($element_name);
    $element = "<{$element_name} ";
    $attrs = [];

    if($attributes) {
        foreach($attributes as $name => $value) {
            $attrs[] = $name . '="' . $value .'"';
        }
    }

    $joined_attr = implode(' ', $attrs);
    $element .= $joined_attr;

    if(in_array($element_name, $self_closing)) {
        return $element .= '/>';
    }

    $innerhtml_content = is_array($innerhtml) ? implode($innerhtml) : $innerhtml;
    return $element .= ">{$innerhtml_content}</{$element_name}>";
}