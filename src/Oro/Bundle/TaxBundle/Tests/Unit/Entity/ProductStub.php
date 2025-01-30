<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Entity;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;

class ProductStub extends Product
{
    private ?ProductTaxCode $taxCode = null;

    public function getTaxCode(): ?ProductTaxCode
    {
        return $this->taxCode;
    }

    public function setTaxCode(?ProductTaxCode $taxCode = null): void
    {
        $this->taxCode = $taxCode;
    }
}
