<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Entity;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;

class CustomerStub extends Customer
{
    /**
     * @var TaxCode
     */
    protected $taxCode;

    /**
     * @return TaxCode|null
     */
    public function getTaxCode()
    {
        return $this->taxCode;
    }

    /**
     * @param mixed $taxCode
     */
    public function setTaxCode(CustomerTaxCode $taxCode = null)
    {
        $this->taxCode = $taxCode;
    }
}
