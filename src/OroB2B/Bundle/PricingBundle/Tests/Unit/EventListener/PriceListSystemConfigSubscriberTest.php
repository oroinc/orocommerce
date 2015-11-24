<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;

use OroB2B\Bundle\PricingBundle\EventListener\PriceListSystemConfigSubscriber;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListSystemConfigType;
use OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfigConverterInterface;
use OroB2B\Bundle\PricingBundle\Tests\Unit\SystemConfig\ConfigsGeneratorTrait;

class PriceListSystemConfigSubscriberTest extends \PHPUnit_Framework_TestCase
{
    use ConfigsGeneratorTrait;

    /** @var \PHPUnit_Framework_MockObject_MockObject|PriceListConfigConverterInterface $converter */
    protected $converterMock;

    public function testFormPreSet()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager $configManager */
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $settings = ['oro_b2b_pricing___default_price_lists' => [
            'value' => [[1, 100], [2, 200]]
        ]];

        $event = new ConfigSettingsUpdateEvent($configManager, $settings);
        $converter = $this->getConverterMock();
        $convertedConfigs = $this->createConfigs(2);

        $converter->expects($this->once())
            ->method('convertFromSaved')
            ->with($settings['oro_b2b_pricing___default_price_lists']['value'])
            ->willReturn($convertedConfigs);

        $subscriber = new PriceListSystemConfigSubscriber($converter);
        $subscriber->formPreSet($event);

        $expected = ['oro_b2b_pricing___default_price_lists' => [
            'value' => [PriceListSystemConfigType::COLLECTION_FIELD_NAME => $convertedConfigs]
        ]];
        $this->assertEquals($expected, $event->getSettings());
    }

    public function testBeforeSave()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager $configManager */
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $settings = ['oro_b2b_pricing.default_price_lists' => [
            'value' => [PriceListSystemConfigType::COLLECTION_FIELD_NAME => $this->createConfigs(2)]
        ]];

        $event = new ConfigSettingsUpdateEvent($configManager, $settings);
        $converter = $this->getConverterMock();

        $converter->expects($this->once())
            ->method('convertBeforeSave')
            ->willReturn([[1, 100], [2, 200]]);

        $subscriber = new PriceListSystemConfigSubscriber($converter);
        $subscriber->beforeSave($event);

        $expected = ['oro_b2b_pricing.default_price_lists' => [
            'value' => [[1, 100], [2, 200]]
        ]];
        $this->assertEquals($expected, $event->getSettings());
    }

    public function testGetSubscribedEvents()
    {
        $subscriber = new PriceListSystemConfigSubscriber($this->getConverterMock());
        $this->assertEquals([
            ConfigSettingsUpdateEvent::FORM_PRESET => 'formPreSet',
            ConfigSettingsUpdateEvent::BEFORE_SAVE => 'beforeSave'
        ], $subscriber->getSubscribedEvents());
    }

    /**
     * @return PriceListConfigConverterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getConverterMock()
    {
        if (!$this->converterMock) {
            $this->converterMock = $this
                ->getMockBuilder('OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfigConverterInterface')
                ->disableOriginalConstructor()
                ->getMock();
        }

        return $this->converterMock;
    }
}
