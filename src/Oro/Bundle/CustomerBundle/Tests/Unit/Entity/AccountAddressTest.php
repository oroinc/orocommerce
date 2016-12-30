<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Entity;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddressToAddressType;

class AccountAddressTest extends AbstractAddressTest
{
    public function testProperties()
    {
        parent::testProperties();

        static::assertPropertyAccessors($this->address, [
            ['frontendOwner', new Account()],
        ]);
    }

    /**
     * @return AccountAddress
     */
    protected function createAddressEntity()
    {
        return new AccountAddress();
    }

    /**
     * @return CustomerAddressToAddressType
     */
    protected function createAddressToTypeEntity()
    {
        return new CustomerAddressToAddressType();
    }
}
