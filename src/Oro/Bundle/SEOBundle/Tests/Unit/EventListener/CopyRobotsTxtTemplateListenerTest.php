<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Oro\Bundle\SEOBundle\EventListener\CopyRobotsTxtTemplateListener;
use Oro\Bundle\SEOBundle\Manager\RobotsTxtFileManager;
use Oro\Bundle\SEOBundle\Sitemap\Event\OnSitemapDumpFinishEvent;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Website\WebsiteInterface;

class CopyRobotsTxtTemplateListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var RobotsTxtFileManager|\PHPUnit\Framework\MockObject\MockObject */
    private $robotsTxtFileManager;

    /** @var WebsiteInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $website;

    /** @var OnSitemapDumpFinishEvent */
    private $event;

    protected function setUp(): void
    {
        $this->website = new Website();
        $this->event = new OnSitemapDumpFinishEvent($this->website, 123);
        $this->robotsTxtFileManager = $this->createMock(RobotsTxtFileManager::class);
    }

    public function testOnSitemapDumpStorageWithAlreadyDumpedData()
    {
        $listener = new CopyRobotsTxtTemplateListener(
            $this->robotsTxtFileManager,
            __DIR__ . '/fixtures/'
        );

        $this->robotsTxtFileManager->expects(self::once())
            ->method('isContentFileExist')
            ->with($this->website)
            ->willReturn(true);

        $this->robotsTxtFileManager->expects(self::never())
            ->method('getFileNameByWebsite')
            ->with($this->website);

        $this->robotsTxtFileManager->expects(self::never())
            ->method('dumpContent');

        $listener->onSitemapDumpStorage($this->event);
    }

    public function testOnSitemapDumpStorageWithExistingDomainDistFile()
    {
        $listener = new CopyRobotsTxtTemplateListener(
            $this->robotsTxtFileManager,
            __DIR__ . '/fixtures/'
        );

        $this->robotsTxtFileManager->expects(self::once())
            ->method('isContentFileExist')
            ->with($this->website)
            ->willReturn(false);

        $this->robotsTxtFileManager->expects(self::once())
            ->method('getFileNameByWebsite')
            ->with($this->website)
            ->willReturn('robots.domain.com.txt');

        $this->robotsTxtFileManager->expects(self::once())
            ->method('dumpContent')
            ->with("#test domain robots file\n", $this->website);

        $listener->onSitemapDumpStorage($this->event);
    }

    public function testOnSitemapDumpStorageWithExistingDefaultDistFile()
    {
        $listener = new CopyRobotsTxtTemplateListener(
            $this->robotsTxtFileManager,
            __DIR__ . '/fixtures/'
        );

        $this->robotsTxtFileManager->expects(self::once())
            ->method('isContentFileExist')
            ->with($this->website)
            ->willReturn(false);

        $this->robotsTxtFileManager->expects(self::once())
            ->method('getFileNameByWebsite')
            ->with($this->website)
            ->willReturn('robots.another.com.txt');

        $this->robotsTxtFileManager->expects(self::once())
            ->method('dumpContent')
            ->with("#test robots file\n", $this->website);

        $listener->onSitemapDumpStorage($this->event);
    }

    public function testOnSitemapDumpStorageWithNotExistingFile()
    {
        $expectedContent = <<<TEXT
# www.robotstxt.org/
# www.google.com/support/webmasters/bin/answer.py?hl=en&answer=156449

User-agent: *

TEXT;
        $listener = new CopyRobotsTxtTemplateListener($this->robotsTxtFileManager, '/not_existing_path/');

        $this->robotsTxtFileManager->expects(self::once())
            ->method('dumpContent')
            ->with($expectedContent, $this->website);

        $listener->onSitemapDumpStorage($this->event);
    }
}
