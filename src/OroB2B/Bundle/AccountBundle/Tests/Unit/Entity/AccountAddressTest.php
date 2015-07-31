<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Entity;

use OroB2B\Bundle\AccountBundle\Entity\AccountAddress;
use OroB2B\Bundle\AccountBundle\Entity\AccountAddressToAddressType;

class AccountAddressTest extends AbstractAddressTest
{
    /**
     * @return AccountAddress
     */
    protected function createAddressEntity()
    {
        return new AccountAddress();
    }

    /**
     * @return AccountAddressToAddressType
     */
    protected function createAddressToTypeEntity()
    {
        return new AccountAddressToAddressType();
    }
}
