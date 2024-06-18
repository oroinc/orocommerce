<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Entity;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;

class CustomerStub extends Customer
{
    private ?CustomerTaxCode $taxCode = null;

    public function getTaxCode(): ?CustomerTaxCode
    {
        return $this->taxCode;
    }

    public function setTaxCode(CustomerTaxCode $taxCode = null): void
    {
        $this->taxCode = $taxCode;
    }
}
