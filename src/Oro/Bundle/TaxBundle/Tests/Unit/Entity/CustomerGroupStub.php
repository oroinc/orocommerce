<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Entity;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;

class CustomerGroupStub extends CustomerGroup
{
    private ?CustomerTaxCode $taxCode = null;

    public function getTaxCode(): ?CustomerTaxCode
    {
        return $this->taxCode;
    }

    public function setTaxCode(?CustomerTaxCode $taxCode = null): void
    {
        $this->taxCode = $taxCode;
    }
}
