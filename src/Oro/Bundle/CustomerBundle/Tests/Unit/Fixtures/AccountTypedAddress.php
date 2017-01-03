<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Fixtures;

use Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TypedAddressOwner;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;

class CustomerTypedAddress extends CustomerAddress
{
    /** @var TypedAddressOwner */
    protected $frontendOwner;

    /**
     * @return TypedAddressOwner
     */
    public function getFrontendOwner()
    {
        return $this->frontendOwner;
    }

    /**
     * @param Account $frontendOwner
     * @return CustomerTypedAddress
     */
    public function setFrontendOwner(Account $frontendOwner)
    {
        $this->frontendOwner = $frontendOwner;

        return $this;
    }
}
