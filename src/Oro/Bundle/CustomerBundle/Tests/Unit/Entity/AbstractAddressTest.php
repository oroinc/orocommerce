<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\CustomerBundle\Entity\AbstractAddressToAddressType;
use Oro\Bundle\CustomerBundle\Entity\AbstractDefaultTypedAddress;

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

    public function testProperties()
    {
        static::assertPropertyAccessors($this->address, [
            ['systemOrganization', new Organization()],
            ['owner', new User()],
            ['phone', '11111111111']
        ]);
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
