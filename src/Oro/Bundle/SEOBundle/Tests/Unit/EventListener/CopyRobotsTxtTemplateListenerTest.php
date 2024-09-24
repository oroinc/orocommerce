<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Oro\Bundle\SEOBundle\EventListener\CopyRobotsTxtTemplateListener;
use Oro\Bundle\SEOBundle\Manager\RobotsTxtFileManager;
use Oro\Bundle\SEOBundle\Manager\RobotsTxtTemplateManager;
use Oro\Bundle\SEOBundle\Sitemap\Event\OnSitemapDumpFinishEvent;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Website\WebsiteInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CopyRobotsTxtTemplateListenerTest extends TestCase
{
    private RobotsTxtFileManager|MockObject $robotsTxtFileManager;

    private RobotsTxtTemplateManager|MockObject $robotsTxtTemplateManager;

    private WebsiteInterface|MockObject $website;

    private OnSitemapDumpFinishEvent $event;

    private CopyRobotsTxtTemplateListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->website = new Website();
        $this->event = new OnSitemapDumpFinishEvent($this->website, 123);
        $this->robotsTxtFileManager = $this->createMock(RobotsTxtFileManager::class);
        $this->robotsTxtTemplateManager = $this->createMock(RobotsTxtTemplateManager::class);

        $this->listener = new CopyRobotsTxtTemplateListener(
            $this->robotsTxtFileManager,
            $this->robotsTxtTemplateManager
        );
    }

    public function testOnSitemapDumpStorageWithAlreadyDumpedData(): void
    {
        $this->robotsTxtFileManager->expects(self::once())
            ->method('isContentFileExist')
            ->with($this->website)
            ->willReturn(true);

        $this->robotsTxtTemplateManager->expects(self::never())
            ->method('getTemplateContent');

        $this->robotsTxtFileManager->expects(self::never())
            ->method('dumpContent');

        $this->listener->onSitemapDumpStorage($this->event);
    }

    public function testOnSitemapDumpStorage(): void
    {
        $this->robotsTxtFileManager->expects(self::once())
            ->method('isContentFileExist')
            ->with($this->website)
            ->willReturn(false);

        $templateContent = "#test domain robots file\n";
        $this->robotsTxtTemplateManager->expects(self::once())
            ->method('getTemplateContent')
            ->with($this->website)
            ->willReturn($templateContent);

        $this->robotsTxtFileManager->expects(self::once())
            ->method('dumpContent')
            ->with($templateContent, $this->website);

        $this->listener->onSitemapDumpStorage($this->event);
    }
}
