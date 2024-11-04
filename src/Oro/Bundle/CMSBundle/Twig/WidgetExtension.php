<?php

namespace Oro\Bundle\CMSBundle\Twig;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetRenderer;
use Oro\Bundle\CMSBundle\ContentWidget\WysiwygWidgetIconRenderer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to render a content widget:
 *   - widget
 *   - widget_icon
 */
class WidgetExtension extends AbstractExtension
{
    private ContentWidgetRenderer $contentWidgetRenderer;
    private WysiwygWidgetIconRenderer $widgetIconRenderer;

    public function __construct(
        ContentWidgetRenderer $contentWidgetRenderer,
        WysiwygWidgetIconRenderer $widgetIconRenderer
    ) {
        $this->contentWidgetRenderer = $contentWidgetRenderer;
        $this->widgetIconRenderer = $widgetIconRenderer;
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('widget', [$this->contentWidgetRenderer, 'render'], ['is_safe' => ['html']]),
            new TwigFunction('widget_icon', [$this->widgetIconRenderer, 'render'], ['is_safe' => ['html']]),
        ];
    }
}
