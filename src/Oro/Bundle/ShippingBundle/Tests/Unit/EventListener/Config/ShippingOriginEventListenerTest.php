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
    /** @var ShippingOriginEventListener */
    protected $listener;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ShippingOriginModelFactory */
    protected $shippingOriginModelFactory;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager */
    protected $configManager;

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

    protected function setUp(): void
    {
        $this->shippingOriginModelFactory = $this->createMock(ShippingOriginModelFactory::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->listener = new ShippingOriginEventListener($this->shippingOriginModelFactory);
    }

    protected function tearDown(): void
    {
        unset($this->shippingOriginModelFactory, $this->configManager, $this->listener);
    }

    public function testFormPreSetWithoutKey()
    {
        $this->shippingOriginModelFactory->expects($this->never())->method($this->anything());

        $event = new ConfigSettingsUpdateEvent($this->configManager, []);

        $this->listener->formPreSet($event);

        $this->assertEquals([], $event->getSettings());
    }

    public function testFormPreSet()
    {
        $shippingOrigin = (new ShippingOrigin(self::$defaultData))
            ->setCountry(new Country('US'))
            ->setRegion(new Region('US-AL'));

        $settings = [
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
        ];

        $event = new ConfigSettingsUpdateEvent($this->configManager, $settings);

        $this->shippingOriginModelFactory->expects($this->once())
            ->method('create')
            ->willReturn($shippingOrigin);

        $this->listener->formPreSet($event);

        $this->assertEquals(['oro_shipping___shipping_origin' => ['value' => $shippingOrigin]], $event->getSettings());
    }

    public function testBeforeSaveWithoutKey()
    {
        $this->shippingOriginModelFactory->expects($this->never())->method($this->anything());

        $event = new ConfigSettingsUpdateEvent($this->configManager, []);

        $this->listener->beforeSave($event);

        $this->assertEquals([], $event->getSettings());
    }

    public function testBeforeSaveNotModel()
    {
        $this->shippingOriginModelFactory->expects($this->never())->method($this->anything());

        $settings = ['value' => null];
        $event = new ConfigSettingsUpdateEvent($this->configManager, $settings);

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

        $this->shippingOriginModelFactory->expects($this->never())->method($this->anything());

        $settings = ['value' => $shippingOrigin];
        $event = new ConfigSettingsUpdateEvent($this->configManager, $settings);

        $this->listener->beforeSave($event);

        $this->assertEquals(['value' => self::$defaultData], $event->getSettings());
    }

    public function testBeforeSaveNoAddress()
    {
        $address = 'some_value';
        $settings = ['value' => $address];
        $event = new ConfigSettingsUpdateEvent($this->configManager, $settings);

        $this->shippingOriginModelFactory->expects($this->never())->method($this->anything());
        $this->listener->beforeSave($event);

        $this->assertEquals($settings, $event->getSettings());
    }
}
