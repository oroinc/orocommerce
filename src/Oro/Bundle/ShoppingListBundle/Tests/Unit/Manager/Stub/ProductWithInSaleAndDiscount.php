<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Manager\Stub;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class ProductWithInSaleAndDiscount extends Product
{
    private bool $inSale = false;
    private bool $discount = false;
    private ?ProductUnitPrecision $precision = null;

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    #[\Override]
    public function getUnitPrecision($unitCode)
    {
        return $this->precision;
    }

    public function setUnitPrecision(?ProductUnitPrecision $precision): void
    {
        $this->precision = $precision;
    }

    public function isInSale(): bool
    {
        return $this->inSale;
    }

    public function setInSale(bool $inSale): void
    {
        $this->inSale = $inSale;
    }

    public function isDiscount(): bool
    {
        return $this->discount;
    }

    public function setDiscount(bool $discount): void
    {
        $this->discount = $discount;
    }
}
