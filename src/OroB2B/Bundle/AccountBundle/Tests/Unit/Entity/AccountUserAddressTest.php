<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Entity;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserAddressToAddressType;

class AccountUserAddressTest extends AbstractAddressTest
{
    public function testProperties()
    {
        parent::testProperties();

        static::assertPropertyAccessors($this->address, [
            ['frontendOwner', new AccountUser()],
        ]);
    }

    /**
     * @return AccountUserAddress
     */
    protected function createAddressEntity()
    {
        return new AccountUserAddress();
    }

    /**
     * @return AccountUserAddressToAddressType
     */
    protected function createAddressToTypeEntity()
    {
        return new AccountUserAddressToAddressType();
    }
}
