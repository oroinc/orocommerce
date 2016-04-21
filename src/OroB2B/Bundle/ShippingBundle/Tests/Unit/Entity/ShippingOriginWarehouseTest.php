<?php

namespace OroB2B\Bundle\ShippingBundle\Bundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingOriginWarehouse;

class ShippingOriginWarehouseTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $now = new \DateTime('now');

        $countryMock = $this->getMockBuilder('Oro\Bundle\AddressBundle\Entity\Country')
            ->disableOriginalConstructor()
            ->getMock();

        $warehouseMock = $this->getMockBuilder('OroB2B\Bundle\WarehouseBundle\Entity\Warehouse')
            ->disableOriginalConstructor()
            ->getMock();

        $regionMock = $this->getMock('Oro\Bundle\AddressBundle\Entity\Region', [], ['combinedCode']);

        $properties = [
            'id' => ['id', 1],
            'country' => ['country', $countryMock],
            'city' => ['city', 'city'],
            'postalCode' => ['postalCode', '12345'],
            'region' => ['region', $regionMock],
            'regionText' => ['regionText', 'test region'],
            'street' => ['street', 'street'],
            'street2' => ['street2', 'street2'],
            'created' => ['created', $now],
            'updated' => ['updated', $now],
            'warehouse' => ['warehouse', $warehouseMock],
        ];

        $shippingOriginWarehouse = new ShippingOriginWarehouse();

        $this->assertPropertyAccessors($shippingOriginWarehouse, $properties);
    }

    public function testBeforeSave()
    {
        $shippingOriginWarehouse = new ShippingOriginWarehouse();
        $shippingOriginWarehouse->beforeSave();
        $this->assertInstanceOf('\DateTime', $shippingOriginWarehouse->getCreated());
        $this->assertInstanceOf('\DateTime', $shippingOriginWarehouse->getUpdated());
    }

    public function testPreUpdate()
    {
        $shippingOriginWarehouse = new ShippingOriginWarehouse();
        $shippingOriginWarehouse->preUpdate();
        $this->assertInstanceOf('\DateTime', $shippingOriginWarehouse->getUpdated());
    }
}
