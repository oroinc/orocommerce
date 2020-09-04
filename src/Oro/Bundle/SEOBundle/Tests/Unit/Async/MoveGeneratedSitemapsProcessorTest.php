<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Async;

use Oro\Bundle\SEOBundle\Async\MoveGeneratedSitemapsProcessor;
use Oro\Bundle\SEOBundle\Async\Topics;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\GaufretteFilesystemAdapter;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class MoveGeneratedSitemapsProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var GaufretteFilesystemAdapter|\PHPUnit\Framework\MockObject\MockObject */
    private $fileSystemAdapter;

    /** @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface */
    private $logger;

    private $session;

    /** @var MoveGeneratedSitemapsProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->fileSystemAdapter = $this->createMock(GaufretteFilesystemAdapter::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->session = $this->createMock(SessionInterface::class);

        $this->processor = new MoveGeneratedSitemapsProcessor($this->fileSystemAdapter, $this->logger);
    }

    public function testGetSubscribedTopics()
    {
        self::assertEquals([Topics::GENERATE_SITEMAP_MOVE_GENERATED_FILES], $this->processor::getSubscribedTopics());
    }

    public function testProcessOnMessageWithoutWebsiteIdsParameter()
    {
        $message = new DbalMessage();
        $message->setBody(json_encode(['test' => 'data']));

        $this->logger->expects(self::once())
            ->method('critical')
            ->with('Got invalid message.');

        self::assertEquals($this->processor::REJECT, $this->processor->process($message, $this->session));
    }

    public function testProcessOnMessageWithWrongWebsiteIdsParameter()
    {
        $message = new DbalMessage();
        $message->setBody(json_encode(['websiteIds' => 123]));

        $this->logger->expects(self::once())
            ->method('critical')
            ->with('Got invalid message.');

        self::assertEquals($this->processor::REJECT, $this->processor->process($message, $this->session));
    }

    public function testProcess()
    {
        $message = new DbalMessage();
        $message->setBody(json_encode(['websiteIds' => [154, 123]]));

        $this->logger->expects(self::never())
            ->method('critical');

        $this->fileSystemAdapter->expects(self::once())
            ->method('moveSitemaps')
            ->with([154, 123]);

        self::assertEquals($this->processor::ACK, $this->processor->process($message, $this->session));
    }

    public function testProcessWithExceptionDuringMovingOfTheFiles()
    {
        $exception = new \Exception('test exception');
        $message = new DbalMessage();
        $message->setBody(json_encode(['websiteIds' => [154, 123]]));

        $this->logger->expects(self::once())
            ->method('critical')
            ->with(
                'Unexpected exception occurred during moving of the generated sitemaps.',
                [
                    'exception' => $exception,
                    'topic'     => Topics::GENERATE_SITEMAP_MOVE_GENERATED_FILES,
                ]
            );

        $this->fileSystemAdapter->expects(self::once())
            ->method('moveSitemaps')
            ->with([154, 123])
            ->willThrowException($exception);

        self::assertEquals($this->processor::REJECT, $this->processor->process($message, $this->session));
    }
}
