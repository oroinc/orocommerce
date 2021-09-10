<?php

namespace Oro\Bundle\CatalogBundle\Twig;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to get an filtered image or placeholder for a category:
 *   - category_filtered_image
 *   - category_image_placeholder
 */
class CategoryImageExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $container;
    private ?AttachmentManager $attachmentManager = null;
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
            new TwigFunction('category_filtered_image', [$this, 'getCategoryFilteredImage']),
            new TwigFunction('category_image_placeholder', [$this, 'getCategoryImagePlaceholder'])
        ];
    }

    public function getCategoryFilteredImage(?File $file, string $filter): string
    {
        if ($file) {
            return $this->getAttachmentManager()->getFilteredImageUrl($file, $filter);
        }

        return $this->getCategoryImagePlaceholder($filter);
    }

    public function getCategoryImagePlaceholder(string $filter): string
    {
        return $this->getImagePlaceholderProvider()->getPath($filter);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            AttachmentManager::class,
            'oro_catalog.provider.category_image_placeholder' => ImagePlaceholderProviderInterface::class,
        ];
    }

    private function getAttachmentManager(): AttachmentManager
    {
        if (null === $this->attachmentManager) {
            $this->attachmentManager = $this->container->get(AttachmentManager::class);
        }

        return $this->attachmentManager;
    }

    private function getImagePlaceholderProvider(): ImagePlaceholderProviderInterface
    {
        if (null === $this->imagePlaceholderProvider) {
            $this->imagePlaceholderProvider = $this->container->get('oro_catalog.provider.category_image_placeholder');
        }

        return $this->imagePlaceholderProvider;
    }
}
