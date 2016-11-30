<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Entity;

use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\AccountUserAddress;
use Oro\Bundle\CustomerBundle\Entity\AccountUserAddressToAddressType;

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
