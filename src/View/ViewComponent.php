<?php

namespace Cube\View;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionParameter;
use Twig\Markup;

abstract class ViewComponent
{
    protected string $template;

    protected array $attributes = array();

    /**
     * Render template
     *
     * @return mixed
     */
    protected function renderView()
    {
        $class = new ReflectionClass($this);
        $props = $class->getMethod('__construct')->getParameters();
        $context = array();

        every($props, function (ReflectionParameter $param) use (&$context) {
            $context[$param->name] = $this->{$param->name};
        });

        $context['_props'] = $context;
        $context['_attr'] = self::buildAttributes($this->attributes);
        $context['_attrs'] = $this->attributes;
        $view = load_view($this->template, $context);
        return $view;
    }

    /**
     * Render view component
     *
     * @param string $name
     * @param array $attr
     * @return mixed
     */
    public static function render(string $name, array $props = [], array $attr = [])
    {
        $name = strtr($name, [
            ':' => '\\'
        ]);

        $namespace = 'App\\Components\\';
        $component = $namespace . $name;

        if (!class_exists($component)) {
            throw new InvalidArgumentException(
                sprintf('Component "%s" not found', $component)
            );
        }

        $instance = new $component(...$props);
        $instance->attributes = $attr;
        return $instance->renderView();
    }

    /**
     * Prepare attributes
     *
     * @param array $attr
     * @return string
     */
    public static function buildAttributes(array $attr)
    {
        $content = array();
        every($attr, function ($value, $key) use (&$content) {
            $content[] = $key . '="' . $value . '"';
        });

        $content = (implode(' ', $content));
        return new Markup($content, 'utf-8');
    }
}
