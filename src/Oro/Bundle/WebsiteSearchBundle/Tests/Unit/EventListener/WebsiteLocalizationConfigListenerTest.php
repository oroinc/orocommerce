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
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(function (ReindexationRequestEvent $reindexationEvent) {
                    return count($reindexationEvent->getWebsitesIds()) === 0
                        && $reindexationEvent->getFieldGroups() === ['main'];
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
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->never())
            ->method('dispatch');

        $listener = new WebsiteLocalizationConfigListener($eventDispatcher);
        $listener->onLocalizationSettingsChange($eventWithConfigChange);
    }

    public function dataProviderForListenerWillProcessOnlyLocalizationChanges(): array
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

    public function dataProviderForListenerWillNotProcessOnOtherConfigChanges(): array
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
}
