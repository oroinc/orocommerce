<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Entity;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;

class CustomerStub extends Customer
{
    /**
     * @var CustomerTaxCode
     */
    protected $taxCode;

    /**
     * @return CustomerTaxCode|null
     */
    public function getTaxCode()
    {
        return $this->taxCode;
    }

    public function setTaxCode(CustomerTaxCode $taxCode = null)
    {
        $this->taxCode = $taxCode;
    }
}
