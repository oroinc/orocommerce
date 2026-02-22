<?php

namespace Oro\Bundle\CMSBundle\Twig;

use Oro\Bundle\CMSBundle\ContentBlock\ContentBlockRenderer;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to render a content block:
 *   - content_block
 */
class ContentBlockExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('content_block', [$this, 'renderContentBlock'], ['is_safe' => ['html']]),
        ];
    }

    public function renderContentBlock(string $blockAlias): string
    {
        return $this->getContentBlockRenderer()->render($blockAlias);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            ContentBlockRenderer::class
        ];
    }

    private function getContentBlockRenderer(): ContentBlockRenderer
    {
        return $this->container->get(ContentBlockRenderer::class);
    }
}
