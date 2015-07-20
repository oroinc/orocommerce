<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\CustomerBundle\Entity\AbstractAddressToAddressType;
use OroB2B\Bundle\CustomerBundle\Entity\AbstractDefaultTypedAddress;

abstract class AbstractAddressTest extends EntityTestCase
{
    /** @var AddressType */
    protected $billingType;

    /** @var AddressType */
    protected $shippingType;

    /** @var AbstractDefaultTypedAddress */
    protected $address;

    /** @var string */
    protected $addressEntityClass;

    /**
     * Constructs a test case with the given name.
     *
     * @param string $name
     * @param array  $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        $this->addressEntityClass = get_class($this->createAddressEntity());
        $this->billingType = new AddressType(AddressType::TYPE_BILLING);
        $this->shippingType = new AddressType(AddressType::TYPE_SHIPPING);

        parent::__construct($name, $data, $dataName);
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->address = $this->createAddressEntity();
    }

    /**
     * Test children
     */
    public function testAddressesToTypesCollection()
    {
        static::assertPropertyCollections($this->address, [['addressesToTypes', $this->createAddressToTypeEntity()]]);
    }

    public function testAddType()
    {
        $this->address->addType($this->billingType);
        /** @var Collection|AbstractAddressToAddressType[] $addressesToTypes */
        $addressesToTypes = $this->address->getAddressesToTypes();
        $this->assertCount(1, $addressesToTypes);

        $addressesToTypesArray = $addressesToTypes->toArray();
        $addressToType = array_shift($addressesToTypesArray);
        $this->assertEquals($this->address, $addressToType->getAddress());
        $this->assertEquals($this->billingType, $addressToType->getType());

    }

    public function testSetTypes()
    {
        $this->assertCount(0, $this->address->getAddressesToTypes());
        $this->assertInstanceOf(
            $this->addressEntityClass,
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
            $this->addressEntityClass,
            $this->address->removeType($this->billingType)
        );

        /** @var Collection|AbstractAddressToAddressType[] $addressesToTypes */
        $addressesToTypes = $this->address->getAddressesToTypes();
        $this->assertCount(1, $addressesToTypes);
        $addressesToTypesArray = $addressesToTypes->toArray();
        $firstAddressesToType = array_shift($addressesToTypesArray);
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

        /** @var AbstractAddressToAddressType $addressToTypes */
        $addressesToTypesArray = $this->address->getAddressesToTypes()->toArray();
        $addressToTypes = array_shift($addressesToTypesArray);
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
            $this->addressEntityClass,
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

    /**
     * @return AbstractDefaultTypedAddress
     */
    abstract protected function createAddressEntity();

    /**
     * @return AbstractAddressToAddressType
     */
    abstract protected function createAddressToTypeEntity();
}
