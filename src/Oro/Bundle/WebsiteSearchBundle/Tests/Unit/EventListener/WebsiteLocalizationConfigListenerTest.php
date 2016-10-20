<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\EventListener;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\WebsiteSearchBundle\EventListener\WebsiteLocalizationConfigListener;

class WebsiteLocalizationConfigListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataProviderForListenerWillProcessOnlyLocalizationChanges
     * @param ConfigUpdateEvent $eventWithLocalizationChange
     */
    public function testListenerWillProcessOnlyLocalizationChanges(ConfigUpdateEvent $eventWithLocalizationChange)
    {
        $eventDispatcher = $this->getEventDispatcherMock();
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch');

        $listener = new WebsiteLocalizationConfigListener($eventDispatcher);
        $listener->onLocalizationSettingsChange($eventWithLocalizationChange);
    }

    /**
     * @dataProvider dataProviderForListenerWillNotProcessOnOtherConfigChanges
     * @param ConfigUpdateEvent $eventWithConfigChange
     */
    public function testListenerWillNotProcessOnOtherConfigChanges(ConfigUpdateEvent $eventWithConfigChange)
    {
        $eventDispatcher = $this->getEventDispatcherMock();
        $eventDispatcher
            ->expects($this->never())
            ->method('dispatch');

        $listener = new WebsiteLocalizationConfigListener($eventDispatcher);
        $listener->onLocalizationSettingsChange($eventWithConfigChange);
    }

    /**
     * @return array
     */
    public function dataProviderForListenerWillProcessOnlyLocalizationChanges()
    {
        return [
            [
                new ConfigUpdateEvent([
                    WebsiteLocalizationConfigListener::CONFIG_LOCALIZATION_DEFAULT => 1,
                ])
            ],
            [
                new ConfigUpdateEvent([
                    WebsiteLocalizationConfigListener::CONFIG_LOCALIZATION_ENABLED => 1,
                ])
            ],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderForListenerWillNotProcessOnOtherConfigChanges()
    {
        return [
            [
                new ConfigUpdateEvent([])
            ],
            [
                new ConfigUpdateEvent([
                    'other_config_change' => 1,
                ])
            ],
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface
     */
    private function getEventDispatcherMock()
    {
        return $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
    }
}
