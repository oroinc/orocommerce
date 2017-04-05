<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Manager;

use Oro\Bundle\SEOBundle\Exception\RobotsTxtFileManagerException;
use Oro\Bundle\SEOBundle\Manager\RobotsTxtFileManager;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class RobotsTxtFileManagerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var Filesystem|\PHPUnit_Framework_MockObject_MockObject
     */
    private $filesystem;

    /**
     * @var string
     */
    private $path;

    /**
     * @var RobotsTxtFileManager
     */
    private $fileManager;

    protected function setUp()
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->path = realpath(__DIR__ . '/fixtures');
        $this->fileManager = new RobotsTxtFileManager($this->logger, $this->filesystem, $this->path);
    }

    public function testGetContentWhenThrowsException()
    {
        $this->path = 'invalidpath';
        $message = sprintf('An error occurred while reading robots.txt file from %s', $this->getFullName());
        $this->logger->expects($this->once())
            ->method('error')
            ->with($message);

        $this->expectException(RobotsTxtFileManagerException::class);
        $this->expectExceptionMessage($message);

        $this->fileManager = new RobotsTxtFileManager($this->logger, $this->filesystem, $this->path);
        $this->fileManager->getContent();
    }

    public function testGetContent()
    {
        $content = $this->fileManager->getContent();
        $this->logger->expects($this->never())
            ->method('error');

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

        $message = sprintf('An error occurred while writing robots.txt file to %s', $this->getFullName());
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

    /**
     * @return string
     */
    private function getFullName()
    {
        return implode(DIRECTORY_SEPARATOR, [$this->path, RobotsTxtFileManager::ROBOTS_TXT_FILENAME]);
    }
}
