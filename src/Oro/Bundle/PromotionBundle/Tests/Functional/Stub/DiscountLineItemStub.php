<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\Stub;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;

class DiscountLineItemStub extends DiscountLineItem
{
    /**
     * @var Product
     */
    private $product;

    /**
     * @param Product $product
     * @return $this
     */
    public function setProduct(Product $product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getProduct()
    {
        return $this->product;
    }
}
