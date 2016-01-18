<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Provider;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\Address;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\TaxBundle\Provider\TaxationAddressProvider;
use OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

class TaxationAddressProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TaxationSettingsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $settingsProvider;

    /**
     * @var TaxationAddressProvider
     */
    protected $addressProvider;

    protected function setUp()
    {
        $this->settingsProvider = $this
            ->getMockBuilder('\OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->addressProvider = new TaxationAddressProvider($this->settingsProvider);
    }

    protected function tearDown()
    {
        unset($this->settingsProvider, $this->addressProvider);
    }

    public function testGetOriginAddress()
    {
        $address = new Address();

        $this->settingsProvider
            ->expects($this->once())
            ->method('getOrigin')
            ->willReturn($address);

        $this->assertSame($address, $this->addressProvider->getOriginAddress());
    }

    public function testGetAddressForTaxationWithOrigin()
    {
        $order = new Order();
        $address = new Address();

        $this->settingsProvider
            ->expects($this->once())
            ->method('isOriginDefaultAddressType')
            ->willReturn(true);

        $this->settingsProvider
            ->expects($this->once())
            ->method('getOrigin')
            ->willReturn($address);

        $this->settingsProvider
            ->expects($this->never())
            ->method('getDestination');

        $this->assertEquals($address, $this->addressProvider->getAddressForTaxation($order));
    }

    /**
     * @dataProvider getAddressForTaxationProvider
     * @param AbstractAddress|null $expectedResult
     * @param string $destination
     * @param OrderAddress $billingAddress
     * @param OrderAddress $shippingAddress
     */
    public function testGetAddressForTaxation(
        $expectedResult,
        $destination,
        OrderAddress $billingAddress,
        OrderAddress $shippingAddress
    ) {
        $this->settingsProvider
            ->expects($this->once())
            ->method('isOriginDefaultAddressType')
            ->willReturn(false);

        $this->settingsProvider
            ->expects($this->never())
            ->method('getOrigin');

        $this->settingsProvider
            ->expects($this->once())
            ->method('getDestination')
            ->willReturn($destination);

        $order = new Order();
        $order->setBillingAddress($billingAddress);
        $order->setShippingAddress($shippingAddress);

        // TODO: Add exception rules logic

        $this->assertSame($expectedResult, $this->addressProvider->getAddressForTaxation($order));
    }

    /**
     * @return array
     */
    public function getAddressForTaxationProvider()
    {
        $billingAddress = new OrderAddress();
        $shippingAddress = new OrderAddress();

        return [
            'destination billing address' => [
                $billingAddress,
                TaxationSettingsProvider::DESTINATION_BILLING_ADDRESS,
                $billingAddress,
                $shippingAddress
            ],
            'destination shipping address' =>[
                $shippingAddress,
                TaxationSettingsProvider::DESTINATION_SHIPPING_ADDRESS,
                $billingAddress,
                $shippingAddress
            ]
            ,
            'destination null address' =>[
                null,
                null,
                $billingAddress,
                $shippingAddress
            ]
        ];
    }
}
