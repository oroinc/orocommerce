<?php

namespace Oro\Bundle\CMSBundle\Twig;

use Oro\Bundle\ApiBundle\Provider\ApiUrlResolver;
use Oro\Bundle\CMSBundle\Entity\ContentTemplate;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions for {@see ContentTemplate} Preview Images.
 */
class ContentTemplateImageExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('content_template_preview_image_placeholder', [$this, 'getPreviewImagePlaceholder']),
        ];
    }

    public function getPreviewImagePlaceholder(string $filter, string $format = ''): string
    {
        return $this->getImagePlaceholderProvider()->getPath(
            $filter,
            $format,
            $this->getApiUrlResolver()->getEffectiveReferenceType()
        );
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            'oro_cms.provider.content_template_preview_image_placeholder' => ImagePlaceholderProviderInterface::class,
            ApiUrlResolver::class
        ];
    }

    private function getImagePlaceholderProvider(): ImagePlaceholderProviderInterface
    {
        return $this->container->get('oro_cms.provider.content_template_preview_image_placeholder');
    }

    private function getApiUrlResolver(): ApiUrlResolver
    {
        return $this->container->get(ApiUrlResolver::class);
    }
}
