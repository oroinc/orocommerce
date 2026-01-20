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
    private ContainerInterface $container;

    private ?ImagePlaceholderProviderInterface $imagePlaceholderProvider = null;
    private ?ApiUrlResolver $apiUrlResolver = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'content_template_preview_image_placeholder',
                [$this, 'getPreviewImagePlaceholder']
            ),
        ];
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            'oro_cms.provider.content_template_preview_image_placeholder' => ImagePlaceholderProviderInterface::class,
            'oro_api.provider.api_url_resolver' => ApiUrlResolver::class,
        ];
    }

    public function getPreviewImagePlaceholder(string $filter, string $format = ''): string
    {
        $referenceType = $this->getEffectiveReferenceType();

        return $this->getImagePlaceholderProvider()->getPath($filter, $format, $referenceType);
    }

    private function getImagePlaceholderProvider(): ImagePlaceholderProviderInterface
    {
        if (null === $this->imagePlaceholderProvider) {
            $this->imagePlaceholderProvider = $this->container
                ->get('oro_cms.provider.content_template_preview_image_placeholder');
        }

        return $this->imagePlaceholderProvider;
    }

    private function getApiUrlResolver(): ApiUrlResolver
    {
        if (null === $this->apiUrlResolver) {
            $this->apiUrlResolver = $this->container->get('oro_api.provider.api_url_resolver');
        }

        return $this->apiUrlResolver;
    }

    private function getEffectiveReferenceType(): int
    {
        return $this->getApiUrlResolver()->getEffectiveReferenceType();
    }
}
