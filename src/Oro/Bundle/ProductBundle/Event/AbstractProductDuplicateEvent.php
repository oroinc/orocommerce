<?php

namespace Oro\Bundle\ProductBundle\Event;

use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractProductDuplicateEvent extends Event
{
    /**
     * @var Product
     */
    protected $product;

    /**
     * @var Product
     */
    protected $sourceProduct;

    public function __construct(Product $product, Product $sourceProduct)
    {
        $this->product = $product;
        $this->sourceProduct = $sourceProduct;
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @return Product
     */
    public function getSourceProduct()
    {
        return $this->sourceProduct;
    }
}
