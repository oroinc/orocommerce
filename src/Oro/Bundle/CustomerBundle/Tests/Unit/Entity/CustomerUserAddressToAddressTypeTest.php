<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Entity;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddressToAddressType;

class CustomerUserAddressToAddressTypeTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

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
     * @return CustomerUserAddressToAddressType
     */
    protected function createAddressToAddressTypeEntity()
    {
        return new CustomerUserAddressToAddressType();
    }

    /**
     * @return CustomerUserAddress
     */
    protected function createAddressEntity()
    {
        return new CustomerUserAddress();
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
