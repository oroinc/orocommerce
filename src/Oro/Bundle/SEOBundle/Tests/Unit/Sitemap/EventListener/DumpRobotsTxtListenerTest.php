<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Sitemap\EventListener;

use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\SEOBundle\Manager\RobotsTxtManager;
use Oro\Bundle\SEOBundle\Sitemap\Dumper\SitemapDumper;
use Oro\Bundle\SEOBundle\Sitemap\Event\OnSitemapDumpFinishEvent;
use Oro\Bundle\SEOBundle\Sitemap\EventListener\DumpRobotsTxtListener;
use Oro\Bundle\SEOBundle\Sitemap\Exception\LogicException;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\SitemapFilesystemAdapter;
use Oro\Bundle\SEOBundle\Sitemap\Storage\SitemapStorageFactory;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Website\WebsiteInterface;

class DumpRobotsTxtListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const SITEMAP_VERSION = '14543456';

    const SITEMAP_DIR = 'sitemap';

    /**
     * @var RobotsTxtManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $robotsTxtManager;

    /**
     * @var CanonicalUrlGenerator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $canonicalUrlGenerator;

    /**
     * @var SitemapFilesystemAdapter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sitemapFilesystemAdapter;

    /**
     * @var DumpRobotsTxtListener
     */
    private $listener;

    protected function setUp()
    {
        $this->robotsTxtManager = $this->getMockBuilder(RobotsTxtManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->canonicalUrlGenerator = $this->getMockBuilder(CanonicalUrlGenerator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sitemapFilesystemAdapter = $this->getMockBuilder(SitemapFilesystemAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener = new DumpRobotsTxtListener(
            $this->robotsTxtManager,
            $this->canonicalUrlGenerator,
            $this->sitemapFilesystemAdapter,
            self::SITEMAP_DIR
        );
    }

    public function testOnSitemapDumpStorageWithNotDefaultWebsite()
    {
        $website = $this->createWebsite(1, false);

        $event = new OnSitemapDumpFinishEvent($website, self::SITEMAP_VERSION);
        $this->sitemapFilesystemAdapter->expects($this->never())
            ->method('getSitemapFiles');
        $this->canonicalUrlGenerator->expects($this->never())
            ->method('getAbsoluteUrl');
        $this->robotsTxtManager->expects($this->never())
            ->method('changeByKeyword');
        $this->listener->onSitemapDumpStorage($event);
    }

    /**
     * @dataProvider onSitemapDumpStorageWhenThrowsExceptionProvider
     */
    public function testOnSitemapDumpStorageWhenThrowsException()
    {
        $website = $this->createWebsite(1, true);
        $event = new OnSitemapDumpFinishEvent($website, self::SITEMAP_VERSION);
        $this->sitemapFilesystemAdapter->expects($this->once())
            ->method('getSitemapFiles')
            ->with(
                $website,
                SitemapFilesystemAdapter::ACTUAL_VERSION,
                SitemapDumper::getFilenamePattern(SitemapStorageFactory::TYPE_SITEMAP_INDEX)
            )
            ->willReturn(new \ArrayIterator());
        $this->canonicalUrlGenerator->expects($this->never())
            ->method('getAbsoluteUrl');
        $this->robotsTxtManager->expects($this->never())
            ->method('changeByKeyword');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot find sitemap index file.');
        $this->listener->onSitemapDumpStorage($event);
    }

    public function onSitemapDumpStorageWhenThrowsExceptionProvider()
    {
        return [
            'when no index files' => [
                'sitemapFiles' => new \ArrayIterator(),
                'exceptionMessage' => 'Cannot find sitemap index file.',
            ],
            'when more than one index files' => [
                'sitemapFiles' => new \ArrayIterator([new \SplFileInfo('some_name'), new \SplFileInfo('some_name_2')]),
                'exceptionMessage' => 'There are more than one index files.',
            ],
        ];
    }

    public function testOnSitemapDumpStorage()
    {
        $websiteId = 777;
        $website = $this->createWebsite($websiteId, true);
        $event = new OnSitemapDumpFinishEvent($website, self::SITEMAP_VERSION);
        $filename = 'some_file_name.txt';
        $this->sitemapFilesystemAdapter->expects($this->once())
            ->method('getSitemapFiles')
            ->with(
                $website,
                SitemapFilesystemAdapter::ACTUAL_VERSION,
                SitemapDumper::getFilenamePattern(SitemapStorageFactory::TYPE_SITEMAP_INDEX)
            )
            ->willReturn(new \ArrayIterator([new \SplFileInfo($filename)]));

        $url = 'http://example.com/robots.txt';
        $this->canonicalUrlGenerator->expects($this->once())
            ->method('getAbsoluteUrl')
            ->with(
                sprintf(
                    '%s/%s/%s/%s',
                    self::SITEMAP_DIR,
                    $websiteId,
                    SitemapFilesystemAdapter::ACTUAL_VERSION,
                    $filename
                ),
                $website
            )
            ->willReturn($url);

        $this->robotsTxtManager->expects($this->once())
            ->method('changeByKeyword')
            ->with(RobotsTxtManager::KEYWORD_SITEMAP, $url);
        $this->listener->onSitemapDumpStorage($event);
    }

    /**
     * @param int $websiteId
     * @param bool $isDefault
     * @return WebsiteInterface
     */
    private function createWebsite($websiteId, $isDefault)
    {
        /** @var WebsiteInterface|\PHPUnit_Framework_MockObject_MockObject $website */
        $website = $this->createMock(WebsiteInterface::class);
        $website
            ->expects($this->any())
            ->method('isDefault')
            ->willReturn($isDefault);

        $website
            ->expects($this->any())
            ->method('getId')
            ->willReturn($websiteId);

        return $website;
    }
}
