<?php

namespace Oro\Bundle\CMSBundle\Twig;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetRenderer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to render a content widget:
 *   - widget
 */
class WidgetExtension extends AbstractExtension
{
    /** @var ContentWidgetRenderer */
    private $contentWidgetRenderer;

    public function __construct(ContentWidgetRenderer $contentWidgetRenderer)
    {
        $this->contentWidgetRenderer = $contentWidgetRenderer;
    }

    /**
     * {@inheritDoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('widget', [$this->contentWidgetRenderer, 'render'], ['is_safe' => ['html']]),
        ];
    }
}
