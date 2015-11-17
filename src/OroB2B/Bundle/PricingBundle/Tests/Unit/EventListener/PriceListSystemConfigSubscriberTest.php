<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use OroB2B\Bundle\PricingBundle\EventListener\PriceListSystemConfigSubscriber;
use OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfigBag;
use OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfigConverterInterface;
use OroB2B\Bundle\PricingBundle\Tests\Unit\SystemConfig\ConfigsGeneratorTrait;

class PriceListSystemConfigSubscriberTest extends \PHPUnit_Framework_TestCase
{
    use ConfigsGeneratorTrait;

    public function testFormPreSet()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager $configManager */
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $settings = ['oro_b2b_pricing___default_price_lists' => [
            'value' => [[1, 100], [2, 200]]
        ]];

        $bag = new PriceListConfigBag();

        $event = new ConfigSettingsUpdateEvent($configManager, $settings);

        /** @var \PHPUnit_Framework_MockObject_MockObject|PriceListConfigConverterInterface $converter */
        $converter = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfigConverterInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $converter->expects($this->once())
            ->method('convertFromSaved')
            ->with($settings['oro_b2b_pricing___default_price_lists']['value'])
            ->willReturn($bag);

        $subscriber = new PriceListSystemConfigSubscriber($converter);
        $subscriber->formPreSet($event);

        $expected = ['oro_b2b_pricing___default_price_lists' => [
            'value' => $bag
        ]];
        $this->assertEquals($expected, $event->getSettings());
    }

    public function testBeforeSave()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager $configManager */
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $bag = new PriceListConfigBag();
        $bag->setConfigs(new ArrayCollection($this->createConfigs(2)));

        $settings = ['oro_b2b_pricing.default_price_lists' => [
            'value' => $bag
        ]];


        $event = new ConfigSettingsUpdateEvent($configManager, $settings);

        /** @var \PHPUnit_Framework_MockObject_MockObject|PriceListConfigConverterInterface $converter */
        $converter = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfigConverterInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $converter->expects($this->once())
            ->method('convertBeforeSave')
            ->with($settings['oro_b2b_pricing.default_price_lists']['value'])
            ->willReturn([[1, 100], [2, 200]]);

        $subscriber = new PriceListSystemConfigSubscriber($converter);
        $subscriber->beforeSave($event);

        $expected = ['oro_b2b_pricing.default_price_lists' => [
            'value' => [[1, 100], [2, 200]]
        ]];
        $this->assertEquals($expected, $event->getSettings());
    }
}
