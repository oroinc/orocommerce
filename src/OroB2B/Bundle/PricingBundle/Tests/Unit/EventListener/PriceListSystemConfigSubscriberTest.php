<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\EventListener;

use OroB2B\Bundle\PricingBundle\Event\PriceListCollectionChange;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
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

    /** @var  EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

    public function testFormPreSet()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager $configManager */
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $settings = [
            'oro_b2b_pricing___default_price_lists' => [
                'value' => [[1, 100], [2, 200]],
            ],
        ];

        $event = new ConfigSettingsUpdateEvent($configManager, $settings);
        $converter = $this->getConverterMock();
        $convertedConfigs = $this->createConfigs(2);

        $converter->expects($this->once())
            ->method('convertFromSaved')
            ->with($settings['oro_b2b_pricing___default_price_lists']['value'])
            ->willReturn($convertedConfigs);

        $subscriber = new PriceListSystemConfigSubscriber($converter, $this->getEventDispatcher());
        $subscriber->formPreSet($event);

        $expected = [
            'oro_b2b_pricing___default_price_lists' => [
                'value' => $convertedConfigs,
            ],
        ];
        $this->assertEquals($expected, $event->getSettings());
    }

    public function testBeforeSave()
    {
        $values = $this->createConfigs(2);
        $settings = [
            'oro_b2b_pricing.default_price_lists' => [
                'value' => $values,
            ],
        ];
        $converted = [
            ['priceList' => 1, 'priority' => 100],
            ['priceList' => 2, 'priority' => 200],
        ];
        $expected = [
            'oro_b2b_pricing.default_price_lists' => [
                'value' => $converted,
            ],
        ];

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

        $subscriber = new PriceListSystemConfigSubscriber($converter, $this->getEventDispatcher());
        $subscriber->beforeSave($event);

        $this->assertEquals($expected, $event->getSettings());
    }

    /**
     * @dataProvider updateAfterDataProvider
     * @param array $changeSet
     * @param boolean $dispatch
     */
    public function testUpdateAfter($changeSet, $dispatch)
    {
        $converted = [
            ['priceList' => 1, 'priority' => 100],
            ['priceList' => 2, 'priority' => 200],
        ];
        $values = $this->createConfigs(2);
        /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager $configManager */
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $settings = [
            'oro_b2b_pricing.default_price_lists' => [
                'value' => $values,
            ],
        ];
        $event = new ConfigSettingsUpdateEvent($configManager, $settings);
        $converter = $this->getConverterMock();

        $converter->expects($this->once())
            ->method('convertBeforeSave')
            ->with($values)
            ->willReturn($converted);

        $subscriber = new PriceListSystemConfigSubscriber($converter, $this->getEventDispatcher());
        $subscriber->beforeSave($event);
        if ($dispatch) {
            $this->eventDispatcher
                ->expects($this->once())
                ->method('dispatch')
                ->with(PriceListCollectionChange::BEFORE_CHANGE, new PriceListCollectionChange());
        } else {
            $this->eventDispatcher
                ->expects($this->never())
                ->method('dispatch');
        }
        $subscriber->updateAfter(new ConfigUpdateEvent($changeSet));
    }

    /**
     * @return array
     */
    public function updateAfterDataProvider()
    {
        return [
                'changed' => ['changeSet' => ['some', 'changes'], 'dispatch' => true],
                'notChanged' => ['changeSet' => [], 'dispatch' => false],
        ];
    }

    public function testUpdateAfterWithNotApplicable()
    {
        $converter = $this->getConverterMock();
        $subscriber = new PriceListSystemConfigSubscriber($converter, $this->getEventDispatcher());
        $this->eventDispatcher
            ->expects($this->never())
            ->method('dispatch');
        $subscriber->updateAfter(new ConfigUpdateEvent([]));
    }

    public function testGetSubscribedEvents()
    {
        $subscriber = new PriceListSystemConfigSubscriber($this->getConverterMock(), $this->getEventDispatcher());
        $this->assertEquals(
            [
                ConfigSettingsUpdateEvent::FORM_PRESET => 'formPreSet',
                ConfigSettingsUpdateEvent::BEFORE_SAVE => 'beforeSave',
                ConfigUpdateEvent::EVENT_NAME => 'updateAfter',
            ],
            $subscriber->getSubscribedEvents()
        );
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

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface
     */
    protected function getEventDispatcher()
    {
        if (!$this->eventDispatcher) {
            $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        }

        return $this->eventDispatcher;
    }
}
