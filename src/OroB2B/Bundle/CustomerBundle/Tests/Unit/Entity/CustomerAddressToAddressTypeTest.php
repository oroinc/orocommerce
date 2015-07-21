<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\CustomerBundle\Entity\CustomerAddress;
use OroB2B\Bundle\CustomerBundle\Entity\CustomerAddressToAddressType;

class CustomerAddressToAddressTypeTest extends EntityTestCase
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
     * @return CustomerAddressToAddressType
     */
    protected function createAddressToAddressTypeEntity()
    {
        return new CustomerAddressToAddressType();
    }

    /**
     * @return CustomerAddress
     */
    protected function createAddressEntity()
    {
        return new CustomerAddress();
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
