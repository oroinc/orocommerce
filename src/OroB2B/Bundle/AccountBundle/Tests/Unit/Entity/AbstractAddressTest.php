<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\AccountBundle\Entity\AbstractAddressToAddressType;
use OroB2B\Bundle\AccountBundle\Entity\AbstractDefaultTypedAddress;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
abstract class AbstractAddressTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

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
     * @param array $data
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
    public function testTypesCollection()
    {
        static::assertPropertyCollections($this->address, [['types', $this->billingType]]);
    }

    public function testAddType()
    {
        $this->address->addType($this->billingType);
        $types = $this->address->getTypes();
        $this->assertCount(1, $types);
        $this->assertEquals($this->billingType, $types->first());
    }

    public function testSetTypes()
    {
        $this->assertCount(0, $this->address->getTypes());
        $this->assertInstanceOf(
            $this->addressEntityClass,
            $this->address->setTypes(new ArrayCollection([$this->billingType]))
        );
        $this->assertCount(1, $this->address->getTypes());

        // addressesToTypes should be cleared
        $this->address->setTypes(new ArrayCollection([$this->shippingType]));
        $this->assertCount(1, $this->address->getTypes());
    }

    public function testGetTypes()
    {
        $types = new ArrayCollection([$this->billingType, $this->shippingType]);
        $this->address->setTypes($types);
        $this->assertEquals($types, $this->address->getTypes());
    }

    public function testOwner()
    {
        $owner = new User();
        $this->address->setOwner($owner);
        $this->assertEquals($owner, $this->address->getOwner());
    }

    public function testSystemOrganization()
    {
        $organization = new Organization();
        $this->address->setOrganization($organization);
        $this->assertEquals($organization, $this->address->getOrganization());
    }

    public function testRemoveTypes()
    {
        $testTypes = new ArrayCollection([$this->billingType, $this->shippingType]);
        $this->address->setTypes($testTypes);
        $this->assertInstanceOf(
            $this->addressEntityClass,
            $this->address->removeType($this->billingType)
        );

        $types = $this->address->getTypes();
        $this->assertCount(1, $types);
        $this->assertEquals($this->shippingType, $types->first());
    }

    public function testGetDefaults()
    {
        $this->assertCount(0, $this->address->getDefaults());
        $this->assertInstanceOf(
            'Doctrine\Common\Collections\ArrayCollection',
            $this->address->getDefaults()
        );
        $this->assertFalse($this->address->hasDefault('billing'));
        $this->assertFalse($this->address->hasDefault('shipping'));

        $this->address->addType($this->billingType);
        $this->address->addType($this->shippingType);
        $this->assertCount(0, $this->address->getDefaults());

        $this->address->setDefaults([$this->billingType, $this->shippingType]);
        $this->assertCount(2, $this->address->getDefaults());
        $this->assertTrue($this->address->hasDefault('billing'));
        $this->assertTrue($this->address->hasDefault('shipping'));
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
