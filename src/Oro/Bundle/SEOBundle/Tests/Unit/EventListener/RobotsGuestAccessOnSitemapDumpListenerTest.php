<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Oro\Bundle\SEOBundle\EventListener\RobotsGuestAccessOnSitemapDumpListener;
use Oro\Bundle\SEOBundle\Manager\RobotsTxtIndexingRulesBySitemapManager;
use Oro\Bundle\SEOBundle\Sitemap\Event\OnSitemapDumpFinishEvent;
use Oro\Component\Website\WebsiteInterface;

class RobotsGuestAccessOnSitemapDumpListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var RobotsTxtIndexingRulesBySitemapManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $robotsTxtIndexingRulesManager;

    /**
     * @var RobotsGuestAccessOnSitemapDumpListener
     */
    private $robotsGuestAccessOnSitemapDumpListener;

    protected function setUp(): void
    {
        $this->robotsTxtIndexingRulesManager = $this->createMock(RobotsTxtIndexingRulesBySitemapManager::class);
        $this->robotsGuestAccessOnSitemapDumpListener = new RobotsGuestAccessOnSitemapDumpListener(
            $this->robotsTxtIndexingRulesManager
        );
    }

    public function testonSitemapDumpStorage()
    {
        $event = $this->createMock(OnSitemapDumpFinishEvent::class);
        $website = $this->createMock(WebsiteInterface::class);
        $event->expects(static::any())
            ->method('getWebsite')
            ->willReturn($website);
        $website->expects(static::once())
            ->method('isDefault')
            ->willReturn(true);

        $event->expects(static::once())
            ->method('getVersion')
            ->willReturn(12);

        $this->robotsTxtIndexingRulesManager
            ->expects(static::once())
            ->method('flush');

        $this->robotsGuestAccessOnSitemapDumpListener->onSitemapDumpStorage($event);
    }

    public function testonSitemapDumpStorageNotDefaultWebsite()
    {
        $event = $this->createMock(OnSitemapDumpFinishEvent::class);
        $website = $this->createMock(WebsiteInterface::class);
        $event->expects(static::once())
            ->method('getWebsite')
            ->willReturn($website);

        $website->expects(static::once())
            ->method('isDefault')
            ->willReturn(false);

        $this->robotsGuestAccessOnSitemapDumpListener->onSitemapDumpStorage($event);
    }
}
