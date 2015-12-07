<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;

use OroB2B\Bundle\PricingBundle\EventListener\PriceListSystemConfigSubscriber;
use OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfigConverter;
use OroB2B\Bundle\PricingBundle\Tests\Unit\SystemConfig\ConfigsGeneratorTrait;

class PriceListSystemConfigSubscriberTest extends \PHPUnit_Framework_TestCase
{
    use ConfigsGeneratorTrait;

    /** @var \PHPUnit_Framework_MockObject_MockObject|PriceListConfigConverter $converter */
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
            'value' => $convertedConfigs
        ]];
        $this->assertEquals($expected, $event->getSettings());
    }

    /**
     * @param array $settings
     * @param array $values
     * @param array $converted
     * @param array $expected
     * @dataProvider beforeSaveDataProvider
     */
    public function testBeforeSave(array $settings, array $values, array $converted, array $expected)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager $configManager */
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $event = new ConfigSettingsUpdateEvent($configManager, $settings);
        $converter = $this->getConverterMock();

        $converter->expects($this->once())
            ->method('convertBeforeSave')
            ->with($values)
            ->willReturn($converted);

        $subscriber = new PriceListSystemConfigSubscriber($converter);
        $subscriber->beforeSave($event);

        $this->assertEquals($expected, $event->getSettings());
    }

    /**
     * @return array
     */
    public function beforeSaveDataProvider()
    {
        $values = $this->createConfigs(2);
        $converted = [
            ['priceList' => 1, 'priority' => 100],
            ['priceList' => 2, 'priority' => 200]
        ];

        return [
            'data format' => [
                'settings' => [
                    'oro_b2b_pricing.default_price_lists' => [
                        'value' => $values
                    ]
                ],
                'values' => $values,
                'converted' => $converted,
                'expected' => [
                    'oro_b2b_pricing.default_price_lists' => [
                        'value' => $converted
                    ]
                ],
            ],
        ];
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
     * @return PriceListConfigConverter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getConverterMock()
    {
        if (!$this->converterMock) {
            $this->converterMock = $this
                ->getMockBuilder('OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfigConverter')
                ->disableOriginalConstructor()
                ->getMock();
        }

        return $this->converterMock;
    }
}
