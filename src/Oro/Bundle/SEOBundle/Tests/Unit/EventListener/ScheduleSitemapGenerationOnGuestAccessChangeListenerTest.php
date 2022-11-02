<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\SEOBundle\Async\SitemapGenerationScheduler;
use Oro\Bundle\SEOBundle\EventListener\ScheduleSitemapGenerationOnGuestAccessChangeListener;

class ScheduleSitemapGenerationOnGuestAccessChangeListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SitemapGenerationScheduler|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scheduleSitemapGenerationProvider;

    /**
     * @var ScheduleSitemapGenerationOnGuestAccessChangeListener
     */
    private $generateSitemapListener;

    protected function setUp(): void
    {
        $this->scheduleSitemapGenerationProvider = $this->createMock(SitemapGenerationScheduler::class);
        $this->generateSitemapListener = new ScheduleSitemapGenerationOnGuestAccessChangeListener(
            $this->scheduleSitemapGenerationProvider
        );
    }

    public function testOnConfigUpdate()
    {
        /** @var ConfigUpdateEvent|\PHPUnit\Framework\MockObject\MockObject $event **/
        $event = $this->createMock(ConfigUpdateEvent::class);

        $event->expects(static::once())
            ->method('isChanged')
            ->with('oro_frontend.guest_access_enabled')
            ->willReturn(true);

        $this->scheduleSitemapGenerationProvider->expects(static::once())
            ->method('scheduleSend');

        $this->generateSitemapListener->onConfigUpdate($event);
    }

    public function testOnConfigUpdateFalse()
    {
        /** @var ConfigUpdateEvent|\PHPUnit\Framework\MockObject\MockObject $event **/
        $event = $this->createMock(ConfigUpdateEvent::class);

        $event->expects(static::once())
            ->method('isChanged')
            ->with('oro_frontend.guest_access_enabled')
            ->willReturn(false);

        $this->scheduleSitemapGenerationProvider->expects(static::never())
            ->method('scheduleSend');

        $this->generateSitemapListener->onConfigUpdate($event);
    }
}
