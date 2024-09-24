<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Bundle\WebsiteSearchBundle\EventListener\WebsiteLocalizationConfigListener;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class WebsiteLocalizationConfigListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $dispatcher;

    /** @var WebsiteLocalizationConfigListener */
    private $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->listener = new WebsiteLocalizationConfigListener($this->dispatcher);
    }

    /**
     * @dataProvider processOnlyLocalizationChangesData
     */
    public function testProcessOnlyLocalizationChanges(array $changeSet): void
    {
        $this->dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(new ReindexationRequestEvent([], [], [], true, ['main']), ReindexationRequestEvent::EVENT_NAME);

        $this->listener->onLocalizationSettingsChange(new ConfigUpdateEvent($changeSet, 'website', 1));
    }

    public function processOnlyLocalizationChangesData(): array
    {
        return [
            [
                ['oro_locale.default_localization' => ['old' => 1, 'new' => 2]]
            ],
            [
                ['oro_locale.enabled_localizations' => ['old' => 1, 'new' => 2]]
            ],
        ];
    }

    /**
     * @dataProvider notProcessOnOtherConfigChangesDataProvider
     */
    public function testNotProcessOnOtherConfigChanges(array $changeSet): void
    {
        $this->dispatcher->expects(self::never())
            ->method('dispatch');

        $this->listener->onLocalizationSettingsChange(new ConfigUpdateEvent($changeSet, 'website', 1));
    }

    public function notProcessOnOtherConfigChangesDataProvider(): array
    {
        return [
            [
                []
            ],
            [
                ['other_config_change' => ['old' => 1, 'new' => 2]]
            ],
        ];
    }
}
