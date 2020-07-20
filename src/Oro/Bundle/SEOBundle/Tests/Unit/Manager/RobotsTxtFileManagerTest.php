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

    protected function setUp()
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->urlGenerator = $this->createMock(CanonicalUrlGenerator::class);
        $this->path = realpath(__DIR__ . '/fixtures');
        $this->defaultFilePath = $this->path . '/public';
        $this->fileManager = new RobotsTxtFileManager(
            $this->logger,
            $this->filesystem,
            $this->path
        );
        $this->fileManager->setUrlGenerator($this->urlGenerator);
        $this->fileManager->setDefaultRobotsPath($this->defaultFilePath);
    }

    public function testGetContentWhenThrowsException()
    {
        $this->path = 'invalidpath';
        $this->defaultFilePath = $this->path . '/public';
        $message = sprintf('An error occurred while reading robots.txt file from %s', $this->getFullName());
        $this->logger->expects($this->once())
            ->method('error')
            ->with($message);

        $this->expectException(RobotsTxtFileManagerException::class);
        $this->expectExceptionMessage($message);

        $this->fileManager = new RobotsTxtFileManager(
            $this->logger,
            $this->filesystem,
            $this->path
        );
        $this->fileManager->setUrlGenerator($this->urlGenerator);
        $this->fileManager->setDefaultRobotsPath($this->defaultFilePath);
        $this->fileManager->getContent();
    }

    public function testGetContent()
    {
        $this->logger->expects($this->never())
            ->method('error');

        $content = $this->fileManager->getContent();

        $this->assertStringEqualsFile(
            $this->getFullName(),
            $content
        );
    }

    public function testDumpContentWhenThrowsException()
    {
        $content = 'Some content';

        $exception = new IOException('Exception message');
        $this->filesystem->expects($this->once())
            ->method('dumpFile')
            ->with($this->getFullName(), $content)
            ->willThrowException($exception);

        $message = sprintf(
            'An error occurred while writing robots file to %s',
            $this->getFullName()
        );
        $this->logger->expects($this->once())
            ->method('error')
            ->with($message);

        $this->expectException(RobotsTxtFileManagerException::class);
        $this->expectExceptionMessage($message);

        $this->fileManager->dumpContent($content);
    }

    public function testDumpContent()
    {
        $content = 'Some content';

        $this->filesystem->expects($this->once())
            ->method('dumpFile')
            ->with($this->getFullName(), $content);
        $this->logger->expects($this->never())
            ->method('error');

        $this->fileManager->dumpContent($content);
    }

    public function testDumpContentForWebsiteWithDomainWithoutSubfolder()
    {
        $website = $this->getEntity(Website::class, ['id' => '15']);
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

        $this->fileManager->dumpContentForWebsite($content, $website);
    }

    public function testDumpContentForWebsiteWithDomainWithSubfolder()
    {
        $website = $this->getEntity(Website::class, ['id' => '15']);
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

        $this->fileManager->dumpContentForWebsite($content, $website);
    }

    /**
     * @return string
     */
    private function getFullName()
    {
        return implode(DIRECTORY_SEPARATOR, [$this->defaultFilePath, RobotsTxtFileManager::ROBOTS_TXT_FILENAME]);
    }
}
