<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Bundle\WebsiteSearchBundle\EventListener\WebsiteLocalizationConfigListener;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class WebsiteLocalizationConfigListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider dataProviderForListenerWillProcessOnlyLocalizationChanges
     */
    public function testListenerWillProcessOnlyLocalizationChanges(ConfigUpdateEvent $eventWithLocalizationChange)
    {
        $eventDispatcher = $this->getEventDispatcherMock();
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(function ($reindexationEvent) {
                    /** @var ReindexationRequestEvent $reindexationEvent */
                    return count($reindexationEvent->getWebsitesIds()) === 0;
                }),
                ReindexationRequestEvent::EVENT_NAME
            );

        $listener = new WebsiteLocalizationConfigListener($eventDispatcher);
        $listener->onLocalizationSettingsChange($eventWithLocalizationChange);
    }

    /**
     * @dataProvider dataProviderForListenerWillNotProcessOnOtherConfigChanges
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
     * @return \PHPUnit\Framework\MockObject\MockObject|EventDispatcherInterface
     */
    private function getEventDispatcherMock()
    {
        return $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
    }
}
