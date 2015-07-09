<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\CustomerBundle\Entity\CustomerAddress;
use OroB2B\Bundle\CustomerBundle\Entity\CustomerAddressToAddressType;

class CustomerAddressTest extends EntityTestCase
{
    /** @var AddressType */
    protected $billingType;

    /** @var AddressType */
    protected $shippingType;

    /** @var CustomerAddress */
    protected $address;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->address = $this->createAddressEntity();
        $this->billingType = new AddressType(AddressType::TYPE_BILLING);
        $this->shippingType = new AddressType(AddressType::TYPE_SHIPPING);
    }

    /**
     * Test children
     */
    public function testAddressesToTypesCollection()
    {
        static::assertPropertyCollections($this->address, [['addressesToTypes', new CustomerAddressToAddressType()]]);

        $addressToType = $this->createAddressToTypeEntity();
        $this->assertInstanceOf(
            'OroB2B\Bundle\CustomerBundle\Entity\CustomerAddress',
            $this->address->addAddressesToType($addressToType)
        );
        $this->assertCount(1, $this->address->getAddressesToTypes());

        // duplicate add should be ignored
        $this->address->addAddressesToType($addressToType);
        $this->assertCount(1, $this->address->getAddressesToTypes());

        $otherAddressToType = $this->createAddressToTypeEntity();
        $this->assertInstanceOf(
            'OroB2B\Bundle\CustomerBundle\Entity\CustomerAddress',
            $this->address->removeAddressesToType($otherAddressToType)
        );
        $this->assertCount(1, $this->address->getAddressesToTypes());

        $this->address->removeAddressesToType($addressToType);
        $this->assertCount(0, $this->address->getAddressesToTypes());
    }

    public function testAddType()
    {
        $this->address->addType($this->billingType);
        /** @var Collection|CustomerAddressToAddressType[] $addressesToTypes */
        $addressesToTypes = $this->address->getAddressesToTypes();
        $this->assertCount(1, $addressesToTypes);
        $addressToType = array_shift($addressesToTypes->toArray());
        $this->assertEquals($this->address, $addressToType->getAddress());
        $this->assertEquals($this->billingType, $addressToType->getType());

    }

    public function testSetTypes()
    {
        $this->assertCount(0, $this->address->getAddressesToTypes());
        $this->assertInstanceOf(
            'OroB2B\Bundle\CustomerBundle\Entity\CustomerAddress',
            $this->address->setTypes(new ArrayCollection([$this->billingType]))
        );
        $this->assertCount(1, $this->address->getAddressesToTypes());

        // addressesToTypes should be cleared
        $this->address->setTypes(new ArrayCollection([$this->shippingType]));
        $this->assertCount(1, $this->address->getAddressesToTypes());
    }

    public function testGetTypes()
    {
        $types = new ArrayCollection([$this->billingType, $this->shippingType]);
        $this->address->setTypes($types);
        $this->assertEquals($types, $this->address->getTypes());
    }

    public function testRemoveTypes()
    {
        $types = new ArrayCollection([$this->billingType, $this->shippingType]);
        $this->address->setTypes($types);
        $this->assertInstanceOf(
            'OroB2B\Bundle\CustomerBundle\Entity\CustomerAddress',
            $this->address->removeType($this->billingType)
        );

        /** @var Collection|CustomerAddressToAddressType[] $addressesToTypes */
        $addressesToTypes = $this->address->getAddressesToTypes();
        $this->assertCount(1, $addressesToTypes);
        $firstAddressesToType = array_shift($addressesToTypes->toArray());
        $this->assertEquals($this->shippingType, $firstAddressesToType->getType());
        $this->assertEquals($this->address, $firstAddressesToType->getAddress());
    }

    public function testGetDefaults()
    {
        $this->assertCount(0, $this->address->getDefaults());
        $this->assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $this->address->getDefaults()
        );

        $this->address->addType($this->billingType);
        $this->assertCount(0, $this->address->getDefaults());

        /** @var CustomerAddressToAddressType $addressToTypes */
        $addressToTypes = array_shift($this->address->getAddressesToTypes()->toArray());
        $addressToTypes->setDefault(true);

        $this->assertCount(1, $this->address->getDefaults());
    }

    public function testSetDefaults()
    {
        $types = new ArrayCollection([$this->billingType, $this->shippingType]);
        $this->assertCount(0, $this->address->getDefaults());
        $this->address->setTypes($types);
        $this->assertCount(0, $this->address->getDefaults());

        $this->assertInstanceOf(
            'OroB2B\Bundle\CustomerBundle\Entity\CustomerAddress',
            $this->address->setDefaults([$this->billingType])
        );

        $this->assertCount(1, $this->address->getDefaults());
        $this->address->setDefaults([$this->billingType, $this->shippingType]);
        $this->assertCount(2, $this->address->getDefaults());

        $this->address->setTypes(new ArrayCollection([$this->billingType]));
        $this->assertCount(0, $this->address->getDefaults());
        $this->address->setDefaults([$this->shippingType]);
        $this->assertCount(0, $this->address->getDefaults());
    }

    protected function createAddressEntity()
    {
        return new CustomerAddress();
    }

    private function createAddressToTypeEntity()
    {
        return new CustomerAddressToAddressType();
    }
}
