<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Entity;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;

class CustomerGroupStub extends CustomerGroup
{
    /** @var CustomerTaxCode */
    protected $taxCode;

    public function __construct(?int $id = null)
    {
        parent::__construct();

        $this->id = $id;
    }

    public function setTaxCode(CustomerTaxCode $taxCode = null)
    {
        $this->taxCode = $taxCode;
    }

    /**
     * @return CustomerTaxCode
     */
    public function getTaxCode()
    {
        return $this->taxCode;
    }
}
