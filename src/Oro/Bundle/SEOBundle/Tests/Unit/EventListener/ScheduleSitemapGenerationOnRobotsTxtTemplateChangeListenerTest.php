<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\SEOBundle\Async\SitemapGenerationScheduler;
use Oro\Bundle\SEOBundle\EventListener\ScheduleSitemapGenerationOnRobotsTxtTemplateChangeListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ScheduleSitemapGenerationOnRobotsTxtTemplateChangeListenerTest extends TestCase
{
    private const ROBOTS_TXT_TEMPLATE_KEY = 'oro_seo.sitemap_robots_txt_template';

    private SitemapGenerationScheduler|MockObject $sitemapGenerationScheduler;

    private ScheduleSitemapGenerationOnRobotsTxtTemplateChangeListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->sitemapGenerationScheduler = $this->createMock(SitemapGenerationScheduler::class);

        $this->listener = new ScheduleSitemapGenerationOnRobotsTxtTemplateChangeListener(
            $this->sitemapGenerationScheduler
        );
    }

    public function testOnConfigUpdate(): void
    {
        $event = new ConfigUpdateEvent(
            [
                self::ROBOTS_TXT_TEMPLATE_KEY => [
                    'old' => '#test robots file',
                    'new' => 'User-agent: *',
                ],
            ],
            'global',
            0
        );

        $this->sitemapGenerationScheduler->expects(static::once())
            ->method('scheduleSend');

        $this->listener->onConfigUpdate($event);
    }

    public function testOnConfigNotUpdated(): void
    {
        $event = new ConfigUpdateEvent([], 'global', 0);

        $this->sitemapGenerationScheduler->expects(static::never())
            ->method('scheduleSend');

        $this->listener->onConfigUpdate($event);
    }
}
