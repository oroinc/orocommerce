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
    /** @var AddressModelFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $addressModelFactory;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var AddressEventListener */
    private $listener;

    protected function setUp(): void
    {
        $this->addressModelFactory = $this->createMock(AddressModelFactory::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->listener = new AddressEventListener($this->addressModelFactory);
    }

    private function getEvent(array $settings): ConfigSettingsUpdateEvent
    {
        return new ConfigSettingsUpdateEvent($this->configManager, $settings);
    }

    public function testFormPreSetWithoutKey()
    {
        $event = $this->getEvent([]);

        $this->listener->formPreSet($event);
        $this->assertEquals([], $event->getSettings());
    }

    public function testFormPreSet()
    {
        $event = $this->getEvent([
            'oro_tax___origin_address' => [
                'value' => [
                    'region_text' => 'Alabama',
                    'postal_code' => '35004',
                    'country' => 'US',
                    'region' => 'US-AL',
                ],
            ],
        ]);

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
        $event = $this->getEvent([]);

        $this->addressModelFactory->expects($this->never())->method($this->anything());

        $this->listener->beforeSave($event);
        $this->assertEquals([], $event->getSettings());
    }

    public function testBeforeSaveNotModel()
    {
        $settings = ['value' => null];
        $event = $this->getEvent($settings);

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

        $event = $this->getEvent(['value' => $address]);

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
        $event = $this->getEvent($settings);

        $this->addressModelFactory->expects($this->never())->method($this->anything());

        $this->listener->beforeSave($event);

        $this->assertEquals($settings, $event->getSettings());
    }
}
