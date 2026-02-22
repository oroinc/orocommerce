<?php

namespace Oro\Bundle\CatalogBundle\Twig;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Provider\PictureSourcesProviderInterface;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions for {@see Category} entity.
 */
class CategoryImageExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('category_filtered_image', [$this, 'getCategoryFilteredImage']),
            new TwigFunction('category_image_placeholder', [$this, 'getCategoryImagePlaceholder']),
            new TwigFunction('category_filtered_picture_sources', [$this, 'getCategoryFilteredPictureSources']),
        ];
    }

    public function getCategoryFilteredImage(?File $file, string $filter, string $format = ''): string
    {
        if ($file) {
            return $this->getAttachmentManager()->getFilteredImageUrl($file, $filter, $format);
        }

        return $this->getCategoryImagePlaceholder($filter, $format);
    }

    public function getCategoryImagePlaceholder(string $filter, string $format = ''): string
    {
        return $this->getImagePlaceholderProvider()->getPath($filter, $format);
    }

    /**
     * Returns sources array that can be used in <picture> tag.
     * Adds WebP image variants is current oro_attachment.webp_strategy is "if_supported".
     *
     * @param File|null $file
     * @param string $filterName
     * @param array $attrs Extra attributes to add to <source> tags
     *
     * @return array
     *  [
     *      'src' => '/url/for/default_image.png',
     *      'sources' => [
     *          [
     *              'srcset' => '/url/for/image.png',
     *              'type' => 'image/png',
     *          ],
     *          // ...
     *      ],
     *  ]
     */
    public function getCategoryFilteredPictureSources(
        ?File $file,
        string $filterName = 'original',
        array $attrs = []
    ): array {
        $pictureSources = [];
        if ($file) {
            $pictureSources = $this->getPictureSourcesProvider()->getFilteredPictureSources($file, $filterName);
        } else {
            $pictureSources['src'] = $this->getCategoryImagePlaceholder($filterName);

            $isWebpEnabledIfSupported = $this->getAttachmentManager()->isWebpEnabledIfSupported();
            if ($isWebpEnabledIfSupported) {
                $pictureSources['sources'] = [
                    [
                        'srcset' => $this->getCategoryImagePlaceholder($filterName, 'webp'),
                        'type' => 'image/webp',
                    ],
                ];
            }
        }

        $pictureSources['sources'] = array_map(
            static fn (array $source) => array_merge($source, $attrs),
            $pictureSources['sources'] ?? []
        );

        return $pictureSources;
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            'oro_catalog.provider.category_image_placeholder' => ImagePlaceholderProviderInterface::class,
            'oro_attachment.provider.picture_sources' => PictureSourcesProviderInterface::class,
            AttachmentManager::class
        ];
    }

    private function getImagePlaceholderProvider(): ImagePlaceholderProviderInterface
    {
        return $this->container->get('oro_catalog.provider.category_image_placeholder');
    }

    private function getPictureSourcesProvider(): PictureSourcesProviderInterface
    {
        return $this->container->get('oro_attachment.provider.picture_sources');
    }

    private function getAttachmentManager(): AttachmentManager
    {
        return $this->container->get(AttachmentManager::class);
    }
}
