<?php

namespace Oro\Bundle\ProductBundle\Twig;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;

class ProductImageExtension extends \Twig_Extension
{
    const NAME = 'oro_product_image';

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
            new \Twig_SimpleFunction(
                'oro_product_image_get_sub_path',
                [$this, 'getSubPath']
            )
        ];
    }

    /**
     * @param string $applicationUrl
     * @return string
     */
    public function getSubPath(string $applicationUrl): string
    {
        $matches = [];
        if (preg_match('/^(?:https?\:\/\/)?[^\/\:]+(\/.+)$/', trim($applicationUrl, '/'), $matches)) {
            return $matches[1];
        }

        return '';
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
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
