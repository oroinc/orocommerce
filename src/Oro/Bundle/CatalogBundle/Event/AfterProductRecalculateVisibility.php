<?php

namespace Oro\Bundle\CatalogBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\ProductBundle\Entity\Product;

class AfterProductRecalculateVisibility extends Event
{
    const NAME = 'oro_catalog.event.after_product_recalculate_visibility';

    /** @var Product */
    private $product;

    /**
     * @param Product $product
     */
    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }
}
