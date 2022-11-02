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
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_cms.content_block.renderer' => ContentBlockRenderer::class,
        ];
    }

    private function getContentBlockRenderer(): ContentBlockRenderer
    {
        return $this->container->get('oro_cms.content_block.renderer');
    }
}
