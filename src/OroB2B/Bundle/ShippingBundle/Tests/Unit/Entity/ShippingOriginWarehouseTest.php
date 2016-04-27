<?php

namespace OroB2B\Bundle\ShippingBundle\Bundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\ShippingBundle\Entity\ShippingOriginWarehouse;
use OroB2B\Bundle\ShippingBundle\Model\ShippingOrigin;

class ShippingOriginWarehouseTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    /** @var ShippingOriginWarehouse */
    protected $shippingOriginWarehouse;

    protected function setUp()
    {
        $this->shippingOriginWarehouse = new ShippingOriginWarehouse();
    }

    protected function tearDown()
    {
        unset($this->shippingOriginWarehouse);
    }

    public function testProperties()
    {
        $now = new \DateTime('now');

        $properties = [
            'id' => ['id', 1],
            'country' => ['country', $this->getEntity('Oro\Bundle\AddressBundle\Entity\Country')],
            'city' => ['city', 'city'],
            'postalCode' => ['postalCode', '12345'],
            'region' => ['region', $this->getEntity('Oro\Bundle\AddressBundle\Entity\Region')],
            'regionText' => ['regionText', 'test region'],
            'street' => ['street', 'street'],
            'street2' => ['street2', 'street2'],
            'created' => ['created', $now],
            'updated' => ['updated', $now],
            'warehouse' => ['warehouse', $this->getEntity('OroB2B\Bundle\WarehouseBundle\Entity\Warehouse')]
        ];

        $this->assertPropertyAccessors($this->shippingOriginWarehouse, $properties);
    }

    public function testIsSystem()
    {
        $this->assertFalse($this->shippingOriginWarehouse->isSystem());

        //form type mapping purpose
        $this->shippingOriginWarehouse->setSystem(true);
        $this->assertTrue($this->shippingOriginWarehouse->isSystem());
    }

    public function testPostLoad()
    {
        $this->assertAttributeEmpty('data', $this->shippingOriginWarehouse);

        $this->setProperty($this->shippingOriginWarehouse, 'country', 'test country');
        $this->setProperty($this->shippingOriginWarehouse, 'region', 'test region');
        $this->setProperty($this->shippingOriginWarehouse, 'regionText', 'test region_text');
        $this->setProperty($this->shippingOriginWarehouse, 'postalCode', 'test postalCode');
        $this->setProperty($this->shippingOriginWarehouse, 'city', 'test city');
        $this->setProperty($this->shippingOriginWarehouse, 'street', 'test street');
        $this->setProperty($this->shippingOriginWarehouse, 'street2', 'test street2');

        $this->shippingOriginWarehouse->postLoad();

        $this->assertAttributeEquals(
            new \ArrayObject(
                [
                    'country' => 'test country',
                    'region' => 'test region',
                    'region_text' => 'test region_text',
                    'postalCode' => 'test postalCode',
                    'city' => 'test city',
                    'street' => 'test street',
                    'street2' => 'test street2',
                ]
            ),
            'data',
            $this->shippingOriginWarehouse
        );
    }

    public function testImport()
    {
        $this->assertTrue($this->shippingOriginWarehouse->isEmpty());

        $shippingOrigin = new ShippingOrigin(
            [
                'country' => 'test country',
                'region' => 'test region',
                'region_text' => 'test region_text',
                'postalCode' => 'test postalCode',
                'city' => 'test city',
                'street' => 'test street',
                'street2' => 'test street2'
            ]
        );

        $this->shippingOriginWarehouse->import($shippingOrigin);

        $this->assertAttributeEquals('test country', 'country', $this->shippingOriginWarehouse);
        $this->assertAttributeEquals('test region', 'region', $this->shippingOriginWarehouse);
        $this->assertAttributeEquals('test region_text', 'regionText', $this->shippingOriginWarehouse);
        $this->assertAttributeEquals('test postalCode', 'postalCode', $this->shippingOriginWarehouse);
        $this->assertAttributeEquals('test city', 'city', $this->shippingOriginWarehouse);
        $this->assertAttributeEquals('test street', 'street', $this->shippingOriginWarehouse);
        $this->assertAttributeEquals('test street2', 'street2', $this->shippingOriginWarehouse);
    }

    /**
     * @param object $object
     * @param string $property
     * @param mixed $value
     * @return $this
     */
    protected function setProperty($object, $property, $value)
    {
        $reflection = new \ReflectionProperty(get_class($object), $property);
        $reflection->setAccessible(true);
        $reflection->setValue($object, $value);

        return $this;
    }
}
