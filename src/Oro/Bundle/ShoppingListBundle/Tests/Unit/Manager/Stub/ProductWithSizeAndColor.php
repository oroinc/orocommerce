<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Manager\Stub;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class ProductWithSizeAndColor extends Product
{
    private ?string $size = null;
    private ?string $color = null;
    private ?ProductUnitPrecision $precision = null;

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getSize(): ?string
    {
        return $this->size;
    }

    public function setSize(?string $size): void
    {
        $this->size = $size;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): void
    {
        $this->color = $color;
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
}
