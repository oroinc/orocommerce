<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Stub;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\ScopeBundle\Tests\Unit\Stub\StubScope;

class Scope extends StubScope
{
    private ?int $id = null;

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): ?Customer
    {
        return $this->attributes['customer'] ?? null;
    }

    /**
     * @param Customer $customer
     * @return Scope
     */
    public function setCustomer(Customer $customer): self
    {
        $this->attributes['customer'] = $customer;

        return $this;
    }

    public function getCustomerGroup(): ?CustomerGroup
    {
        return $this->attributes['customerGroup'] ?? null;
    }

    /**
     * @param CustomerGroup $customerGroup
     * @return Scope
     */
    public function setCustomerGroup(CustomerGroup $customerGroup): self
    {
        $this->attributes['customerGroup'] = $customerGroup;

        return $this;
    }
}
