<?php

namespace Oro\Bundle\CatalogBundle\Twig;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Provider\PictureSourcesProvider;
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
    private ContainerInterface $container;

    private ?AttachmentManager $attachmentManager = null;

    private ?PictureSourcesProviderInterface $pictureSourcesProvider = null;

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

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return [
            AttachmentManager::class,
            PictureSourcesProvider::class,
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

    private function getPictureSourcesProvider(): PictureSourcesProviderInterface
    {
        if (null === $this->pictureSourcesProvider) {
            $this->pictureSourcesProvider = $this->container->get(PictureSourcesProvider::class);
        }

        return $this->pictureSourcesProvider;
    }

    private function getImagePlaceholderProvider(): ImagePlaceholderProviderInterface
    {
        if (null === $this->imagePlaceholderProvider) {
            $this->imagePlaceholderProvider = $this->container->get('oro_catalog.provider.category_image_placeholder');
        }

        return $this->imagePlaceholderProvider;
    }
}
