<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Stub;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductLineItem;

class ProductLineItemStub extends ProductLineItem
{
    /** @var Product|null */
    protected $parentProduct;

    public function getParentProduct(): ?Product
    {
        return $this->parentProduct;
    }

    public function setParentProduct(?Product $parentProduct): self
    {
        $this->parentProduct = $parentProduct;

        return $this;
    }
}
