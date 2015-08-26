<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Fixtures;

use Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TypedAddressOwner;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountAddress;

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
