<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Manager;

use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\SEOBundle\Exception\RobotsTxtFileManagerException;
use Oro\Bundle\SEOBundle\Manager\RobotsTxtFileManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class RobotsTxtFileManagerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var Filesystem|\PHPUnit\Framework\MockObject\MockObject */
    private $filesystem;

    /** @var CanonicalUrlGenerator|\PHPUnit\Framework\MockObject\MockObject */
    private $urlGenerator;

    /** @var string */
    private $path;

    /** @var string */
    private $defaultFilePath;

    /**
     * @var RobotsTxtFileManager
     */
    private $fileManager;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->urlGenerator = $this->createMock(CanonicalUrlGenerator::class);
        $this->path = realpath(__DIR__ . '/fixtures');
        $this->defaultFilePath = $this->path . '/public';
        $this->fileManager = new RobotsTxtFileManager(
            $this->logger,
            $this->filesystem,
            $this->urlGenerator,
            $this->defaultFilePath,
            $this->path
        );
    }

    public function testGetContentWhenThrowsException()
    {
        $website = $this->getEntity(Website::class, ['id' => '15']);
        $this->path = 'invalidpath';
        $this->defaultFilePath = $this->path . '/public';
        $message = sprintf(
            'An error occurred while reading robots file from %s',
            $this->getFullName('robots.domain.com.txt')
        );
        $this->logger->expects($this->once())
            ->method('error')
            ->with($message);

        $this->expectException(RobotsTxtFileManagerException::class);
        $this->expectExceptionMessage($message);
        $this->urlGenerator->expects(self::once())
            ->method('getCanonicalDomainUrl')
            ->with($website)
            ->willReturn('http://domain.com/');

        $this->fileManager = new RobotsTxtFileManager(
            $this->logger,
            $this->filesystem,
            $this->urlGenerator,
            $this->defaultFilePath,
            $this->path
        );
        $this->fileManager->getContent($website);
    }

    public function testGetContent()
    {
        $website = $this->getEntity(Website::class, ['id' => '145']);

        $this->logger->expects(self::never())
            ->method('error');
        $this->urlGenerator->expects(self::once())
            ->method('getCanonicalDomainUrl')
            ->with($website)
            ->willReturn('http://domain.com/');


        $content = $this->fileManager->getContent($website);
        $this->assertStringEqualsFile(
            $this->getFullName('robots.domain.com.txt'),
            $content
        );
    }

    public function testDumpContentWhenThrowsException()
    {
        $website = $this->getEntity(Website::class, ['id' => '145']);
        $content = 'Some content';

        $exception = new IOException('Exception message');
        $this->filesystem->expects($this->once())
            ->method('dumpFile')
            ->with($this->getFullName('robots.domain.com.txt'), $content)
            ->willThrowException($exception);

        $message = sprintf(
            'An error occurred while writing robots file to %s',
            $this->getFullName('robots.domain.com.txt')
        );
        $this->logger->expects($this->once())
            ->method('error')
            ->with($message);

        $this->expectException(RobotsTxtFileManagerException::class);
        $this->expectExceptionMessage($message);
        $this->urlGenerator->expects(self::once())
            ->method('getCanonicalDomainUrl')
            ->with($website)
            ->willReturn('http://domain.com/');

        $this->fileManager->dumpContent($content, $website);
    }

    public function testDumpContent()
    {
        $website = $this->getEntity(Website::class, ['id' => '159']);
        $content = 'Some content';

        $this->filesystem->expects($this->once())
            ->method('dumpFile')
            ->with($this->getFullName('robots.domain.com.txt'), $content);
        $this->logger->expects($this->never())
            ->method('error');
        $this->urlGenerator->expects(self::once())
            ->method('getCanonicalDomainUrl')
            ->with($website)
            ->willReturn('http://domain.com/');

        $this->fileManager->dumpContent($content, $website);
    }

    public function testDumpContentForWebsiteWithDomainWithoutSubfolder()
    {
        $website = $this->getEntity(Website::class, ['id' => '4']);
        $content = 'Some content';

        $this->filesystem->expects($this->once())
            ->method('dumpFile')
            ->with($this->path . '/robots.test.com.txt', $content);
        $this->logger->expects($this->never())
            ->method('error');
        $this->urlGenerator->expects(self::once())
            ->method('getCanonicalDomainUrl')
            ->with($website)
            ->willReturn('https://test.com');

        $this->fileManager->dumpContent($content, $website);
    }

    public function testDumpContentForWebsiteWithDomainWithSubfolder()
    {
        $website = $this->getEntity(Website::class, ['id' => '35']);
        $content = 'Some content';

        $this->filesystem->expects($this->once())
            ->method('dumpFile')
            ->with($this->path . '/robots.test.com.txt', $content);
        $this->logger->expects($this->never())
            ->method('error');
        $this->urlGenerator->expects(self::once())
            ->method('getCanonicalDomainUrl')
            ->with($website)
            ->willReturn('https://test.com/subfolder');

        $this->fileManager->dumpContent($content, $website);
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    private function getFullName(string $fileName): string
    {
        return implode(DIRECTORY_SEPARATOR, [$this->path, $fileName]);
    }
}
