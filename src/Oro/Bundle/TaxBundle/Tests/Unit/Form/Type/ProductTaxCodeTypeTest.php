<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Form\Type\AbstractTaxCodeType;
use Oro\Bundle\TaxBundle\Form\Type\ProductTaxCodeType;

class ProductTaxCodeTypeTest extends AbstractTaxCodeTypeTest
{
    #[\Override]
    protected function createTaxCodeType(): AbstractTaxCodeType
    {
        return new ProductTaxCodeType();
    }

    #[\Override]
    protected function getDataClass(): string
    {
        return ProductTaxCode::class;
    }
}
