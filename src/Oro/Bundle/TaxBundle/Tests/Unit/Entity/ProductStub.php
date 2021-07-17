<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Entity;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;

class ProductStub extends Product
{
    /**
     * @var ProductTaxCode
     */
    protected $taxCode;

    /**
     * @return ProductTaxCode|null
     */
    public function getTaxCode()
    {
        return $this->taxCode;
    }

    public function setTaxCode(ProductTaxCode $taxCode = null)
    {
        $this->taxCode = $taxCode;
    }
}
