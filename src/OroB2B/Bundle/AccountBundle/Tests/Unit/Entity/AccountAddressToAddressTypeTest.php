<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Entity;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountAddress;
use OroB2B\Bundle\AccountBundle\Entity\AccountAddressToAddressType;

class AccountAddressToAddressTypeTest extends EntityTestCase
{
    /**
     * Test setters getters
     */
    public function testAccessors()
    {
        $this->assertPropertyAccessors($this->createAddressToAddressTypeEntity(), [
            ['id', 1],
            ['address', $this->createAddressEntity()],
            ['type', $this->createAddressTypeEntity(AddressType::TYPE_BILLING)],
            ['default', true],
        ]);
    }

    /**
     * @return AccountAddressToAddressType
     */
    protected function createAddressToAddressTypeEntity()
    {
        return new AccountAddressToAddressType();
    }

    /**
     * @return AccountAddress
     */
    protected function createAddressEntity()
    {
        return new AccountAddress();
    }

    /**
     * @param string $name
     * @return AddressType
     */
    protected function createAddressTypeEntity($name)
    {
        return new AddressType($name);
    }
}
