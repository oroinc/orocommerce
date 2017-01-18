<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Entity;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddressToAddressType;

class CustomerUserAddressTest extends AbstractAddressTest
{
    public function testProperties()
    {
        parent::testProperties();

        static::assertPropertyAccessors($this->address, [
            ['frontendOwner', new CustomerUser()],
        ]);
    }

    /**
     * @return CustomerUserAddress
     */
    protected function createAddressEntity()
    {
        return new CustomerUserAddress();
    }

    /**
     * @return CustomerUserAddressToAddressType
     */
    protected function createAddressToTypeEntity()
    {
        return new CustomerUserAddressToAddressType();
    }
}
