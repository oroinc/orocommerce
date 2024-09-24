<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\Stub;

use Oro\Bundle\ProductBundle\Model\ProductUnitPrecisionAwareInterface;

class ProductKitItemLineItemUnitPrecisionAwareStub extends ProductKitItemLineItemStub implements
    ProductUnitPrecisionAwareInterface
{
    protected int $productUnitPrecision = 0;

    #[\Override]
    public function getProductUnitPrecision(): int
    {
        return $this->productUnitPrecision;
    }

    public function setProductUnitPrecision(int $productUnitPrecision): self
    {
        $this->productUnitPrecision = $productUnitPrecision;

        return $this;
    }
}
