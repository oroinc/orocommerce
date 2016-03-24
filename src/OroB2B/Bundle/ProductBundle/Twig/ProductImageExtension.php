<?php

namespace OroB2B\Bundle\ProductBundle\Twig;

use Doctrine\Common\Collections\Collection;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductImage;

class ProductImageExtension extends \Twig_Extension
{
    const NAME = 'orob2b_product_image';

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'get_product_images_by_type',
                [$this, 'getProductImagesByType']
            ),
            new \Twig_SimpleFunction(
                'get_first_product_image_by_type',
                [$this, 'getFirstProductImageByType']
            )
        ];
    }

    /**
     * @param Product $product
     * @param string $type
     * @return ProductImage[]|Collection
     */
    public function getProductImagesByType(Product $product, $type)
    {
        return $product->getImages()->filter(function (ProductImage $image) use ($type) {
            return $image->hasType($type);
        });
    }

    /**
     * @param Product $product
     * @param string $type
     * @return ProductImage|null
     */
    public function getFirstProductImageByType(Product $product, $type)
    {
        return $this->getProductImagesByType($product, $type)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
