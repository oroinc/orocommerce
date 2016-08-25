<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Entity;

use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\AccountBundle\Entity\AccountUserAddress;
use Oro\Bundle\AccountBundle\Entity\AccountUserAddressToAddressType;

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
