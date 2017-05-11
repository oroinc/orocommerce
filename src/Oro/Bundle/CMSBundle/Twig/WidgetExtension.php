<?php

namespace Oro\Bundle\CMSBundle\Twig;

use Oro\Bundle\CMSBundle\Widget\WidgetRegistry;

class WidgetExtension extends \Twig_Extension
{
    /** @var WidgetRegistry */
    private $widgetRegistry;

    /**
     * @param WidgetRegistry $widgetRegistry
     */
    public function __construct(WidgetRegistry $widgetRegistry)
    {
        $this->widgetRegistry = $widgetRegistry;
    }

    /**
     * {@inheritDoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('widget', [$this->widgetRegistry, 'getWidget'], ['is_safe' => ['html']]),
        ];
    }
}
