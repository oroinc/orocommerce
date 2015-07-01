<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Fixtures;

use Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TypedAddressOwner;

use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\CustomerBundle\Entity\CustomerAddress;

class CustomerTypedAddress extends CustomerAddress
{
    /**
     * @var TypedAddressOwner
     */
    protected $owner;

    /**
     * @return TypedAddressOwner
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param Customer $owner
     * @return CustomerTypedAddress
     */
    public function setOwner(Customer $owner)
    {
        $this->owner = $owner;

        return $this;
    }
}
