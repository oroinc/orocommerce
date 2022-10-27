<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Manager;

use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\SEOBundle\Exception\RobotsTxtFileManagerException;
use Oro\Bundle\SEOBundle\Manager\RobotsTxtFileManager;
use Oro\Component\Website\WebsiteInterface;
use Psr\Log\LoggerInterface;

class RobotsTxtFileManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FileManager|\PHPUnit\Framework\MockObject\MockObject */
    private $fileManager;

    /** @var CanonicalUrlGenerator|\PHPUnit\Framework\MockObject\MockObject */
    private $urlGenerator;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var RobotsTxtFileManager */
    private $robotsTxtFileManager;

    protected function setUp(): void
    {
        $this->fileManager = $this->createMock(FileManager::class);
        $this->urlGenerator = $this->createMock(CanonicalUrlGenerator::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->robotsTxtFileManager = new RobotsTxtFileManager(
            $this->fileManager,
            $this->urlGenerator,
            $this->logger
        );
    }

    private function getWebsite(int $id): WebsiteInterface
    {
        $website = $this->createMock(WebsiteInterface::class);
        $website->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        return $website;
    }

    public function testGetContentWhenReadingOfFileFailed()
    {
        $website = $this->getWebsite(123);
        $message = 'An error occurred while reading robots.txt file from robots.domain.com.txt';

        $this->urlGenerator->expects($this->once())
            ->method('getCanonicalDomainUrl')
            ->with($website)
            ->willReturn('http://domain.com/');
        $this->fileManager->expects($this->once())
            ->method('getFileContent')
            ->with('robots.domain.com.txt', $this->isFalse())
            ->willReturn(null);
        $this->logger->expects($this->once())
            ->method('error')
            ->with($message);

        $this->expectException(RobotsTxtFileManagerException::class);
        $this->expectExceptionMessage($message);

        $this->robotsTxtFileManager->getContent($website);
    }

    public function testGetContent()
    {
        $website = $this->getWebsite(123);
        $fileContent = 'test content';

        $this->urlGenerator->expects($this->once())
            ->method('getCanonicalDomainUrl')
            ->with($website)
            ->willReturn('http://domain.com/');
        $this->fileManager->expects($this->once())
            ->method('getFileContent')
            ->with('robots.domain.com.txt', $this->isFalse())
            ->willReturn($fileContent);
        $this->logger->expects($this->never())
            ->method($this->anything());

        $this->assertEquals($fileContent, $this->robotsTxtFileManager->getContent($website));
    }

    public function testDumpContentWhenThrowsException()
    {
        $website = $this->getWebsite(123);
        $content = 'Some content';

        $exception = new \Exception('Exception message');
        $message = 'An error occurred while writing robots.txt file to robots.domain.com.txt';

        $this->urlGenerator->expects($this->once())
            ->method('getCanonicalDomainUrl')
            ->with($website)
            ->willReturn('http://domain.com/');
        $this->fileManager->expects($this->once())
            ->method('writeToStorage')
            ->with($content, 'robots.domain.com.txt')
            ->willThrowException($exception);
        $this->logger->expects($this->once())
            ->method('error')
            ->with($message);

        $this->expectException(RobotsTxtFileManagerException::class);
        $this->expectExceptionMessage($message);

        $this->robotsTxtFileManager->dumpContent($content, $website);
    }

    public function testDumpContent()
    {
        $website = $this->getWebsite(123);
        $content = 'Some content';

        $this->urlGenerator->expects($this->once())
            ->method('getCanonicalDomainUrl')
            ->with($website)
            ->willReturn('http://domain.com/');
        $this->fileManager->expects($this->once())
            ->method('writeToStorage')
            ->with($content, 'robots.domain.com.txt');
        $this->logger->expects($this->never())
            ->method($this->anything());

        $this->robotsTxtFileManager->dumpContent($content, $website);
    }

    public function testDumpContentForWebsiteWithDomainWithoutSubfolder()
    {
        $website = $this->getWebsite(123);
        $content = 'Some content';

        $this->urlGenerator->expects($this->once())
            ->method('getCanonicalDomainUrl')
            ->with($website)
            ->willReturn('https://test.com');
        $this->fileManager->expects($this->once())
            ->method('writeToStorage')
            ->with($content, 'robots.test.com.txt');
        $this->logger->expects($this->never())
            ->method($this->anything());

        $this->robotsTxtFileManager->dumpContent($content, $website);
    }

    public function testDumpContentForWebsiteWithDomainWithSubfolder()
    {
        $website = $this->getWebsite(123);
        $content = 'Some content';

        $this->urlGenerator->expects($this->once())
            ->method('getCanonicalDomainUrl')
            ->with($website)
            ->willReturn('https://test.com/subfolder');
        $this->fileManager->expects($this->once())
            ->method('writeToStorage')
            ->with($content, 'robots.test.com.txt');
        $this->logger->expects($this->never())
            ->method($this->anything());

        $this->robotsTxtFileManager->dumpContent($content, $website);
    }
}
