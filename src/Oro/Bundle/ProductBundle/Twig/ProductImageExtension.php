<?php

namespace Oro\Bundle\ProductBundle\Twig;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Provider\PictureSourcesProvider;
use Oro\Bundle\AttachmentBundle\Provider\PictureSourcesProviderInterface;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Helper\ProductImageHelper;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to get images and image types for a {@see Product} entity.
 */
class ProductImageExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $container;

    private ?AttachmentManager $attachmentManager = null;

    private ?PictureSourcesProviderInterface $pictureSourcesProvider = null;

    private ?ImagePlaceholderProviderInterface $imagePlaceholderProvider = null;

    private ?ProductImageHelper $productImageHelper = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('collect_product_images_by_types', [$this, 'collectProductImagesByTypes']),
            new TwigFunction('sort_product_images', [$this, 'sortProductImages']),
            new TwigFunction('product_filtered_image', [$this, 'getProductFilteredImage']),
            new TwigFunction('product_image_placeholder', [$this, 'getProductImagePlaceholder']),
            new TwigFunction('product_filtered_picture_sources', [$this, 'getProductFilteredPictureSources']),
        ];
    }

    /**
     * @param Product $product
     * @param array $imageTypes
     * @return ProductImage[]
     */
    public function collectProductImagesByTypes(Product $product, array $imageTypes): array
    {
        $result = [];
        $productImages = $product->getImages();
        if ($productImages->isEmpty()) {
            return $result;
        }

        /** @var ProductImage[] $result */
        foreach ($imageTypes as $imageType) {
            foreach ($product->getImagesByType($imageType) as $productImage) {
                $result[$productImage->getId()] = $productImage;
            }
        }

        return $this->getProductImageHelper()->sortImages($result);
    }

    /**
     * @param Collection $productImages
     * @return ProductImage[]
     */
    public function sortProductImages(Collection $productImages): array
    {
        return $this->getProductImageHelper()->sortImages($productImages->toArray());
    }

    public function getProductFilteredImage(?File $file, string $filter, string $format = ''): string
    {
        if ($file) {
            return $this->getAttachmentManager()->getFilteredImageUrl($file, $filter, $format);
        }

        return $this->getProductImagePlaceholder($filter, $format);
    }

    public function getProductImagePlaceholder(string $filter, string $format = ''): string
    {
        return $this->getImagePlaceholderProvider()->getPath($filter, $format);
    }

    /**
     * Returns sources array that can be used in <picture> tag.
     * Adds WebP image variants if current oro_attachment.webp_strategy is "if_supported".
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
    public function getProductFilteredPictureSources(
        ?File $file,
        string $filterName = 'original',
        array $attrs = []
    ): array {
        $pictureSources = [];
        if ($file) {
            $pictureSources = $this->getPictureSourcesProvider()->getFilteredPictureSources($file, $filterName);
        } else {
            $pictureSources['src'] = $this->getProductImagePlaceholder($filterName);

            $isWebpEnabledIfSupported = $this->getAttachmentManager()->isWebpEnabledIfSupported();
            if ($isWebpEnabledIfSupported) {
                $pictureSources['sources'] = [
                    [
                        'srcset' => $this->getProductImagePlaceholder($filterName, 'webp'),
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
            'oro_product.provider.product_image_placeholder' => ImagePlaceholderProviderInterface::class,
            'oro_product.helper.product_image_helper' => ProductImageHelper::class,
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
            $this->imagePlaceholderProvider = $this->container->get('oro_product.provider.product_image_placeholder');
        }

        return $this->imagePlaceholderProvider;
    }

    private function getProductImageHelper(): ProductImageHelper
    {
        if (null === $this->productImageHelper) {
            $this->productImageHelper = $this->container->get('oro_product.helper.product_image_helper');
        }

        return $this->productImageHelper;
    }
}
