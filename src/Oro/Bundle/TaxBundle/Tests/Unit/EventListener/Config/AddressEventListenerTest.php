<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener\Config;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\TaxBundle\EventListener\Config\AddressEventListener;
use Oro\Bundle\TaxBundle\Factory\AddressModelFactory;
use Oro\Bundle\TaxBundle\Model\Address;

class AddressEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AddressEventListener */
    protected $listener;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AddressModelFactory */
    protected $addressModelFactory;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager */
    protected $configManager;

    protected function setUp(): void
    {
        $this->addressModelFactory = $this->createMock(AddressModelFactory::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->listener = new AddressEventListener($this->addressModelFactory);
    }

    public function testFormPreSetWithoutKey()
    {
        $event = new ConfigSettingsUpdateEvent($this->configManager, []);

        $this->listener->formPreSet($event);
        $this->assertEquals([], $event->getSettings());
    }

    public function testFormPreSet()
    {
        $settings = [
            'oro_tax___origin_address' => [
                'value' => [
                    'region_text' => 'Alabama',
                    'postal_code' => '35004',
                    'country' => 'US',
                    'region' => 'US-AL',
                ],
            ],
        ];

        $event = new ConfigSettingsUpdateEvent($this->configManager, $settings);

        $address = (new Address(['region_text' => 'Alabama', 'postal_code' => '35004']))
            ->setCountry(new Country('US'))
            ->setRegion(new Region('US-AL'));

        $this->addressModelFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($address);

        $this->listener->formPreSet($event);

        $this->assertEquals(['oro_tax___origin_address' => ['value' => $address]], $event->getSettings());
    }

    public function testBeforeSaveWithoutKey()
    {
        $event = new ConfigSettingsUpdateEvent($this->configManager, []);

        $this->addressModelFactory->expects($this->never())->method($this->anything());

        $this->listener->beforeSave($event);
        $this->assertEquals([], $event->getSettings());
    }

    public function testBeforeSaveNotModel()
    {
        $settings = ['value' => null];
        $event = new ConfigSettingsUpdateEvent($this->configManager, $settings);

        $this->addressModelFactory->expects($this->never())->method($this->anything());

        $this->listener->beforeSave($event);
        $this->assertEquals($settings, $event->getSettings());
    }

    public function testBeforeSave()
    {
        $country = new Country('US');
        $region = new Region('US-AL');
        $address = new Address(['region_text' => 'Alabama', 'postal_code' => '35004']);
        $address->setCountry($country);
        $address->setRegion($region);

        $event = new ConfigSettingsUpdateEvent($this->configManager, ['value' => $address]);

        $this->addressModelFactory->expects($this->never())->method($this->anything());

        $this->listener->beforeSave($event);

        $this->assertEquals(['value' => [
            'country' => 'US',
            'region' => 'US-AL',
            'region_text' => 'Alabama',
            'postal_code' => '35004'
        ]], $event->getSettings());
    }

    public function testBeforeSaveNoAddress()
    {
        $address ='some_value';
        $settings = ['value' => $address];
        $event = new ConfigSettingsUpdateEvent($this->configManager, $settings);

        $this->addressModelFactory->expects($this->never())->method($this->anything());

        $this->listener->beforeSave($event);

        $this->assertEquals($settings, $event->getSettings());
    }
}
