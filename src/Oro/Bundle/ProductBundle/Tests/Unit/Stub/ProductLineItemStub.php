<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Stub;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductLineItem;

class ProductLineItemStub extends ProductLineItem
{
    /** @var Product|null */
    protected $parentProduct;

    /**
     * @return Product|null
     */
    public function getParentProduct(): ?Product
    {
        return $this->parentProduct;
    }

    /**
     * @param Product|null $parentProduct
     * @return self
     */
    public function setParentProduct(?Product $parentProduct): self
    {
        $this->parentProduct = $parentProduct;

        return $this;
    }
}
