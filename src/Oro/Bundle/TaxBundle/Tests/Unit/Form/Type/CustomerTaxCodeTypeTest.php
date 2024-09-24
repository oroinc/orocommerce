<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Type;

use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Form\Type\AbstractTaxCodeType;
use Oro\Bundle\TaxBundle\Form\Type\CustomerTaxCodeType;

class CustomerTaxCodeTypeTest extends AbstractTaxCodeTypeTest
{
    #[\Override]
    protected function createTaxCodeType(): AbstractTaxCodeType
    {
        return new CustomerTaxCodeType();
    }

    #[\Override]
    protected function getDataClass(): string
    {
        return CustomerTaxCode::class;
    }
}
