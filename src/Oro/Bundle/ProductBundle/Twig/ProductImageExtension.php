<?php

namespace Oro\Bundle\ProductBundle\Twig;

use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
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
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'collect_product_images_by_types',
                [$this, 'collectProductImagesByTypes']
            ),
            new \Twig_SimpleFunction('product_image_placeholder', [$this, 'getProductImagePlaceholder'])
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

        return array_unique($result, SORT_REGULAR);
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
