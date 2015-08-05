<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Fixtures;

use Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TypedAddressOwner;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountAddress;

class AccountTypedAddress extends AccountAddress
{
    /** @var TypedAddressOwner */
    protected $owner;

    /**
     * @return TypedAddressOwner
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param Account $owner
     * @return AccountTypedAddress
     */
    public function setOwner(Account $owner)
    {
        $this->owner = $owner;

        return $this;
    }
}
