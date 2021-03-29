<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;
use Oro\Bundle\PricingBundle\EventListener\PriceListSystemConfigSubscriber;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Bundle\PricingBundle\SystemConfig\PriceListConfigConverter;
use Oro\Bundle\PricingBundle\Tests\Unit\SystemConfig\ConfigsGeneratorTrait;

class PriceListSystemConfigSubscriberTest extends \PHPUnit\Framework\TestCase
{
    use ConfigsGeneratorTrait;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|PriceListConfigConverter
     */
    protected $converterMock;

    /**
     * @var PriceListRelationTriggerHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $changeTriggerHandler;

    /**
     * @var PriceListSystemConfigSubscriber
     */
    protected $subscriber;

    protected function setUp(): void
    {
        $this->converterMock = $this
            ->getMockBuilder(PriceListConfigConverter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->changeTriggerHandler = $this
            ->getMockBuilder(PriceListRelationTriggerHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->subscriber = new PriceListSystemConfigSubscriber(
            $this->converterMock,
            $this->changeTriggerHandler
        );
    }

    public function testFormPreSet()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager $configManager */
        $configManager = $this->getConfigManager();
        $settings = [
            'oro_pricing___default_price_lists' => [
                'value' => [[1, 100], [2, 200]],
            ],
        ];

        $event = new ConfigSettingsUpdateEvent($configManager, $settings);
        $convertedConfigs = $this->createConfigs(2);

        $this->converterMock->expects($this->once())
            ->method('convertFromSaved')
            ->with($settings['oro_pricing___default_price_lists']['value'])
            ->willReturn($convertedConfigs);

        $this->subscriber->formPreSet($event);

        $expected = [
            'oro_pricing___default_price_lists' => [
                'value' => $convertedConfigs,
            ],
        ];
        $this->assertEquals($expected, $event->getSettings());
    }

    public function testBeforeSave()
    {
        $values = $this->createConfigs(2);
        $settings = [
            'value' => $values,
        ];
        $converted = [
            ['priceList' => 1, 'sort_order' => 100],
            ['priceList' => 2, 'sort_order' => 200],
        ];
        $expected = [
            'value' => $converted,
        ];

        $configManager = $this->getConfigManager();

        $event = new ConfigSettingsUpdateEvent($configManager, $settings);

        $this->converterMock->expects($this->once())
            ->method('convertBeforeSave')
            ->with($values)
            ->willReturn($converted);

        $this->subscriber->beforeSave($event);

        $this->assertEquals($expected, $event->getSettings());
    }

    /**
     * @dataProvider updateAfterDataProvider
     * @param array   $changeSet
     * @param boolean $dispatch
     */
    public function testUpdateAfter($changeSet, $dispatch)
    {
        $converted = [
            ['priceList' => 1, 'sort_order' => 100],
            ['priceList' => 2, 'sort_order' => 200],
        ];
        $values = $this->createConfigs(2);
        $configManager = $this->getConfigManager();

        $settings = [
            'value' => $values,
        ];
        $event = new ConfigSettingsUpdateEvent($configManager, $settings);

        $this->converterMock->expects($this->any())
            ->method('convertBeforeSave')
            ->with($values)
            ->willReturn($converted);

        $this->subscriber->beforeSave($event);
        if ($dispatch) {
            $this->changeTriggerHandler
                ->expects($this->once())
                ->method('handleConfigChange');
        } else {
            $this->changeTriggerHandler
                ->expects($this->never())
                ->method('handleConfigChange');
        }
        $this->subscriber->updateAfter(new ConfigUpdateEvent($changeSet));
    }

    /**
     * @return array
     */
    public function updateAfterDataProvider()
    {
        return [
            'changedAndApplicable' => [
                'changeSet' => [
                    Configuration::getConfigKeyByName(Configuration::DEFAULT_PRICE_LISTS) => [
                        'new' => [
                            [
                                'priceList' => 1,
                                'sort_order' => 100
                            ],
                            [
                                'priceList' => 2,
                                'sort_order' => 200
                            ]
                        ],
                        'old' => [
                            [
                                'priceList' => 1,
                                'sort_order' => 200
                            ],
                            [
                                'priceList' => 2,
                                'sort_order' => 100
                            ]
                        ]
                    ]
                ],
                'dispatch' => true,
            ],
            'changedAndNotApplicable' => [
                'changeSet' => [
                    Configuration::getConfigKeyByName('some_option') => [
                        'new' => 'yes',
                        'old' => 'no'
                    ]
                ],
                'dispatch' => false,
            ],
            'notChangedAndApplicable' => [
                'changeSet' => [],
                'dispatch' => false,
            ],
        ];
    }

    public function testUpdateAfterWithNotApplicable()
    {
        $this->changeTriggerHandler
            ->expects($this->never())
            ->method('handleConfigChange');

        $this->subscriber->updateAfter(new ConfigUpdateEvent([]));
    }

    /**
     * @return ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getConfigManager()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager $configManager */
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        return $configManager;
    }
}
