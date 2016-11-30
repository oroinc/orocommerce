<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Fixtures;

use Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TypedAddressOwner;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountAddress;

class AccountTypedAddress extends AccountAddress
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
     * @return AccountTypedAddress
     */
    public function setFrontendOwner(Account $frontendOwner)
    {
        $this->frontendOwner = $frontendOwner;

        return $this;
    }
}
