<?php

namespace Oro\Bundle\ProductBundle\Twig;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Helper\ProductImageHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Introduces the functions to get the image placeholder and types images for the product entity.
 */
class ProductImageExtension extends \Twig_Extension
{
    const NAME = 'oro_product_image';

    /** @var ContainerInterface */
    private $container;

    /** @var ImagePlaceholderProviderInterface */
    private $imagePlaceholderProvider;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return ProductImageHelper
     */
    protected function getProductImageHelper()
    {
        return $this->container->get('oro_product.helper.product_image_helper');
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'collect_product_images_by_types',
                [$this, 'collectProductImagesByTypes']
            ),
            new \Twig_SimpleFunction('product_image_placeholder', [$this, 'getProductImagePlaceholder']),
            new \Twig_SimpleFunction('sort_product_images', [$this, 'sortProductImages'])
        ];
    }

    /**
     * @param Product $product
     * @param array $imageTypes
     * @return ProductImage[]
     */
    public function collectProductImagesByTypes(Product $product, array $imageTypes)
    {
        $result = [];
        $productImages = $product->getImages();
        if ($productImages->isEmpty()) {
            return $result;
        }

        /** @var ProductImage[] $result */
        foreach ($imageTypes as $imageType) {
            $result = array_merge($result, $product->getImagesByType($imageType)->toArray());
        }

        return $this->getProductImageHelper()->sortImages(array_unique($result));
    }

    /**
     * @param Collection $productImages
     * @return ProductImage[]
     */
    public function sortProductImages(Collection $productImages): array
    {
        return $this->getProductImageHelper()->sortImages($productImages->toArray());
    }

    /**
     * @param string $filter
     * @return string
     */
    public function getProductImagePlaceholder(string $filter): string
    {
        if (!$this->imagePlaceholderProvider) {
            $this->imagePlaceholderProvider = $this->container->get('oro_product.provider.product_image_placeholder');
        }

        return $this->imagePlaceholderProvider->getPath($filter);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
