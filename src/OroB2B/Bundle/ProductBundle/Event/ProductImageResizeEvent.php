<?php

namespace OroB2B\Bundle\ProductBundle\Event;

use OroB2B\Bundle\ProductBundle\Entity\ProductImage;
use Symfony\Component\EventDispatcher\Event;

class ProductImageResizeEvent extends Event
{
    const NAME = 'orob2b_product.product_image.resize';

    /**
     * @var ProductImage
     */
    protected $productImage;

    /**
     * @var bool
     */
    protected $forceOption;

    /**
     * @param ProductImage $productImage
     * @param bool $forceOption
     */
    public function __construct(ProductImage $productImage, $forceOption = false)
    {
        $this->productImage = $productImage;
        $this->forceOption = $forceOption;
    }

    /**
     * @return ProductImage
     */
    public function getProductImage()
    {
        return $this->productImage;
    }

    /**
     * @return bool
     */
    public function getForceOption()
    {
        return $this->forceOption;
    }
}
