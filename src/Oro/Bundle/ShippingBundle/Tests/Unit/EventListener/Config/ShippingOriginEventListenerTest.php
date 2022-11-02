<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\EventListener\Config;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\ShippingBundle\EventListener\Config\ShippingOriginEventListener;
use Oro\Bundle\ShippingBundle\Factory\ShippingOriginModelFactory;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;

class ShippingOriginEventListenerTest extends \PHPUnit\Framework\TestCase
{
    private static array $defaultData = [
        'region_text' => 'Alabama',
        'postalCode' => '35004',
        'country' => 'US',
        'region' => 'US-AL',
        'city' => 'City',
        'street' => 'Street',
        'street2' => 'Street2',
    ];

    /** @var ShippingOriginModelFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingOriginFactory;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ShippingOriginEventListener */
    private $listener;

    protected function setUp(): void
    {
        $this->shippingOriginFactory = $this->createMock(ShippingOriginModelFactory::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->listener = new ShippingOriginEventListener($this->shippingOriginFactory);
    }

    private function getEvent(array $settings): ConfigSettingsUpdateEvent
    {
        return new ConfigSettingsUpdateEvent($this->configManager, $settings);
    }

    public function testFormPreSetWithoutKey()
    {
        $this->shippingOriginFactory->expects($this->never())
            ->method($this->anything());

        $event = $this->getEvent([]);

        $this->listener->formPreSet($event);

        $this->assertEquals([], $event->getSettings());
    }

    public function testFormPreSet()
    {
        $shippingOrigin = (new ShippingOrigin(self::$defaultData))
            ->setCountry(new Country('US'))
            ->setRegion(new Region('US-AL'));

        $event = $this->getEvent([
            'oro_shipping___shipping_origin' => [
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
        ]);

        $this->shippingOriginFactory->expects($this->once())
            ->method('create')
            ->willReturn($shippingOrigin);

        $this->listener->formPreSet($event);

        $this->assertEquals(['oro_shipping___shipping_origin' => ['value' => $shippingOrigin]], $event->getSettings());
    }

    public function testBeforeSaveWithoutKey()
    {
        $this->shippingOriginFactory->expects($this->never())
            ->method($this->anything());

        $event = $this->getEvent([]);

        $this->listener->beforeSave($event);

        $this->assertEquals([], $event->getSettings());
    }

    public function testBeforeSaveNotModel()
    {
        $this->shippingOriginFactory->expects($this->never())
            ->method($this->anything());

        $settings = ['value' => null];
        $event = $this->getEvent($settings);

        $this->listener->beforeSave($event);

        $this->assertEquals($settings, $event->getSettings());
    }

    public function testBeforeSave()
    {
        $country = new Country('US');
        $region = new Region('US-AL');
        $shippingOrigin = new ShippingOrigin(self::$defaultData);
        $shippingOrigin->setCountry($country);
        $shippingOrigin->setRegion($region);

        $this->shippingOriginFactory->expects($this->never())
            ->method($this->anything());

        $event = $this->getEvent(['value' => $shippingOrigin]);

        $this->listener->beforeSave($event);

        $this->assertEquals(['value' => self::$defaultData], $event->getSettings());
    }

    public function testBeforeSaveNoAddress()
    {
        $address = 'some_value';

        $settings = ['value' => $address];
        $event = $this->getEvent($settings);

        $this->shippingOriginFactory->expects($this->never())
            ->method($this->anything());
        $this->listener->beforeSave($event);

        $this->assertEquals($settings, $event->getSettings());
    }
}
