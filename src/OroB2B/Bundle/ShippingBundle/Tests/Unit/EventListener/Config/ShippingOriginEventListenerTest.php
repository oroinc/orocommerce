<?php

namespace OroB2B\Bundle\ShippingBundle\Bundle\Tests\Unit\EventListener\Config;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;

use OroB2B\Bundle\ShippingBundle\EventListener\Config\ShippingOriginEventListener;
use OroB2B\Bundle\ShippingBundle\Factory\ShippingOriginModelFactory;
use OroB2B\Bundle\ShippingBundle\Model\ShippingOrigin;

class ShippingOriginEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ShippingOriginEventListener */
    protected $listener;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ShippingOriginModelFactory */
    protected $shippingOriginModelFactory;

    /** @var array */
    protected static $defaultData = [
        'region_text' => 'Alabama',
        'postalCode' => '35004',
        'country' => 'US',
        'region' => 'US-AL',
        'city' => 'City',
        'street' => 'Street',
        'street2' => 'Street2',
    ];

    protected function setUp()
    {
        $this->shippingOriginModelFactory = $this->getMockBuilder(
            'OroB2B\Bundle\ShippingBundle\Factory\ShippingOriginModelFactory'
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener = new ShippingOriginEventListener($this->shippingOriginModelFactory);
    }

    protected function tearDown()
    {
        unset($this->shippingOriginModelFactory, $this->listener);
    }

    public function testFormPreSetWithoutKey()
    {
        $this->shippingOriginModelFactory->expects($this->never())->method($this->anything());

        /** @var ConfigSettingsUpdateEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())->method('getSettings')->willReturn([]);
        $event->expects($this->never())->method('setSettings');

        $this->listener->formPreSet($event);
    }

    public function testFormPreSet()
    {
        $shippingOrigin = (new ShippingOrigin(self::$defaultData))
            ->setCountry(new Country('US'))
            ->setRegion(new Region('US-AL'));

        /** @var ConfigSettingsUpdateEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getSettings')
            ->willReturn(
                [
                    'orob2b_shipping___shipping_origin' => [
                        'value' => [
                            'region_text' => 'Alabama',
                            'postalCode' => '35004',
                            'country' => 'US',
                            'region' => 'US-AL',
                            'city' => 'City',
                            'street' => 'Street',
                            'street2' => 'Street2',
                        ],
                    ],
                ]
            );
        $event
            ->expects($this->once())
            ->method('setSettings')
            ->with(
                $this->callback(
                    function ($settings) use ($shippingOrigin) {
                        $this->assertInternalType('array', $settings);
                        $this->assertArrayHasKey('orob2b_shipping___shipping_origin', $settings);
                        $this->assertInternalType('array', $settings['orob2b_shipping___shipping_origin']);
                        $this->assertArrayHasKey('value', $settings['orob2b_shipping___shipping_origin']);
                        $value = $settings['orob2b_shipping___shipping_origin']['value'];
                        $this->assertInstanceOf('OroB2B\Bundle\ShippingBundle\Model\ShippingOrigin', $value);
                        $this->assertEquals($shippingOrigin, $value);

                        return true;
                    }
                )
            );

        $this->shippingOriginModelFactory->expects($this->once())
            ->method('create')
            ->willReturn($shippingOrigin);

        $this->listener->formPreSet($event);
    }

    public function testBeforeSaveWithoutKey()
    {
        $this->shippingOriginModelFactory->expects($this->never())->method($this->anything());

        /** @var ConfigSettingsUpdateEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())->method('getSettings')->willReturn([]);
        $event->expects($this->never())->method('setSettings');

        $this->listener->beforeSave($event);
    }

    public function testBeforeSaveNotModel()
    {
        $this->shippingOriginModelFactory->expects($this->never())->method($this->anything());

        /** @var ConfigSettingsUpdateEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent')
            ->disableOriginalConstructor()->getMock();
        $event->expects($this->once())->method('getSettings')
            ->willReturn(['orob2b_shipping.shipping_origin' => ['value' => null]]);
        $event->expects($this->never())->method('setSettings');

        $this->listener->beforeSave($event);
    }

    public function testBeforeSave()
    {
        /** @var ConfigSettingsUpdateEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent')
            ->disableOriginalConstructor()->getMock();
        $country = new Country('US');
        $region = new Region('US-AL');
        $shippingOrigin = new ShippingOrigin(self::$defaultData);
        $shippingOrigin->setCountry($country);
        $shippingOrigin->setRegion($region);

        $event->expects($this->once())->method('getSettings')
            ->willReturn(['orob2b_shipping.shipping_origin' => ['value' => $shippingOrigin]]);

        $this->shippingOriginModelFactory->expects($this->never())->method($this->anything());

        $event->expects($this->once())->method('setSettings')->with(
            $this->callback(
                function ($settings) {
                    $this->assertInternalType('array', $settings);
                    $this->assertArrayHasKey('orob2b_shipping.shipping_origin', $settings);
                    $this->assertInternalType('array', $settings['orob2b_shipping.shipping_origin']);
                    $this->assertArrayHasKey('value', $settings['orob2b_shipping.shipping_origin']);
                    $this->assertInternalType('array', $settings['orob2b_shipping.shipping_origin']['value']);
                    $this->assertArrayHasKey('country', $settings['orob2b_shipping.shipping_origin']['value']);
                    $this->assertEquals('US', $settings['orob2b_shipping.shipping_origin']['value']['country']);
                    $this->assertArrayHasKey('region', $settings['orob2b_shipping.shipping_origin']['value']);
                    $this->assertEquals('US-AL', $settings['orob2b_shipping.shipping_origin']['value']['region']);
                    $this->assertArrayHasKey('region_text', $settings['orob2b_shipping.shipping_origin']['value']);
                    $this->assertEquals(
                        'Alabama',
                        $settings['orob2b_shipping.shipping_origin']['value']['region_text']
                    );
                    $this->assertArrayHasKey('postalCode', $settings['orob2b_shipping.shipping_origin']['value']);
                    $this->assertEquals(
                        '35004',
                        $settings['orob2b_shipping.shipping_origin']['value']['postalCode']
                    );

                    return true;
                }
            )
        );
        $this->listener->beforeSave($event);
    }

    public function testBeforeSaveNoAddress()
    {
        /** @var ConfigSettingsUpdateEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent')
            ->disableOriginalConstructor()->getMock();
        $address = 'some_value';
        $event->expects($this->once())->method('getSettings')
            ->willReturn(['orob2b_shipping.shipping_origin' => ['value' => $address]]);
        $this->shippingOriginModelFactory->expects($this->never())->method($this->anything());
        $event->expects($this->never())->method('setSettings');
        $this->listener->beforeSave($event);
    }
}
