<?php

namespace Oro\Bundle\CMSBundle\Twig;

use Oro\Bundle\CMSBundle\Widget\WidgetRegistry;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to render a CMS widget:
 *   - widget
 */
class WidgetExtension extends AbstractExtension
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
            new TwigFunction('widget', [$this->widgetRegistry, 'getWidget'], ['is_safe' => ['html']]),
        ];
    }
}
