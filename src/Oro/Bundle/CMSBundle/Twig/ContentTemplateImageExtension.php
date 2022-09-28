<?php

namespace Oro\Bundle\CMSBundle\Twig;

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
    private ContainerInterface $container;

    private ?ImagePlaceholderProviderInterface $imagePlaceholderProvider = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'content_template_preview_image_placeholder',
                [$this, 'getPreviewImagePlaceholder']
            ),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return [
            'oro_cms.provider.content_template_preview_image_placeholder' => ImagePlaceholderProviderInterface::class,
        ];
    }

    public function getPreviewImagePlaceholder(string $filter, string $format = ''): string
    {
        return $this->getImagePlaceholderProvider()->getPath($filter, $format);
    }

    private function getImagePlaceholderProvider(): ImagePlaceholderProviderInterface
    {
        if (null === $this->imagePlaceholderProvider) {
            $this->imagePlaceholderProvider = $this->container
                ->get('oro_cms.provider.content_template_preview_image_placeholder');
        }

        return $this->imagePlaceholderProvider;
    }
}
