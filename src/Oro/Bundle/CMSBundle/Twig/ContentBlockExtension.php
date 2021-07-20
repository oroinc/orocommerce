<?php

namespace Oro\Bundle\CMSBundle\Twig;

use Oro\Bundle\CMSBundle\ContentBlock\ContentBlockRenderer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to render a content block:
 *   - content_block
 */
class ContentBlockExtension extends AbstractExtension
{
    /** @var ContentBlockRenderer */
    private $contentBlockRenderer;

    public function __construct(ContentBlockRenderer $contentBlockRenderer)
    {
        $this->contentBlockRenderer = $contentBlockRenderer;
    }

    /**
     * {@inheritDoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('content_block', [$this->contentBlockRenderer, 'render'], ['is_safe' => ['html']]),
        ];
    }
}
