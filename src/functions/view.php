<?php

use Cube\View\ViewComponent;

function component(string $name, array $props = [], array $attributes = [])
{
    return ViewComponent::render($name, $props, $attributes);
}
