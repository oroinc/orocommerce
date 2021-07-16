<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Async;

use Oro\Bundle\SEOBundle\Async\MoveGeneratedSitemapsProcessor;
use Oro\Bundle\SEOBundle\Async\Topics;
use Oro\Bundle\SEOBundle\Sitemap\Filesystem\PublicSitemapFilesystemAdapter;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

class MoveGeneratedSitemapsProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var PublicSitemapFilesystemAdapter|\PHPUnit\Framework\MockObject\MockObject */
    private $fileSystemAdapter;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var MoveGeneratedSitemapsProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->fileSystemAdapter = $this->createMock(PublicSitemapFilesystemAdapter::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new MoveGeneratedSitemapsProcessor($this->fileSystemAdapter, $this->logger);
    }

    private function getSession(): SessionInterface
    {
        return $this->createMock(SessionInterface::class);
    }

    private function getMessage(array $body): MessageInterface
    {
        $message = new Message();
        $message->setBody(JSON::encode($body));

        return $message;
    }

    public function testGetSubscribedTopics()
    {
        self::assertEquals(
            [Topics::GENERATE_SITEMAP_MOVE_GENERATED_FILES],
            MoveGeneratedSitemapsProcessor::getSubscribedTopics()
        );
    }

    public function testProcessForWrongParameters()
    {
        $message = $this->getMessage(['key' => 'value']);

        $exception = new UndefinedOptionsException(
            'The option "key" does not exist. Defined options are: "version", "websiteIds".'
        );
        $this->logger->expects(self::once())
            ->method('critical')
            ->with('Got invalid message.', ['exception' => $exception]);

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->getSession())
        );
    }

    public function testProcessForWrongVersionParameter()
    {
        $message = $this->getMessage(['version' => 'wrong', 'websiteIds' => [123]]);

        $exception = new InvalidOptionsException(
            'The option "version" with value "wrong" is expected to be of type "int", but is of type "string".'
        );
        $this->logger->expects(self::once())
            ->method('critical')
            ->with('Got invalid message.', ['exception' => $exception]);

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->getSession())
        );
    }

    public function testProcessForWrongWebsiteIdsParameter()
    {
        $message = $this->getMessage(['version' => 1, 'websiteIds' => 123]);

        $exception = new InvalidOptionsException(
            'The option "websiteIds" with value 123 is expected to be of type "array", but is of type "integer".'
        );
        $this->logger->expects(self::once())
            ->method('critical')
            ->with('Got invalid message.', ['exception' => $exception]);

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->getSession())
        );
    }

    public function testProcess()
    {
        $message = $this->getMessage(['version' => 1, 'websiteIds' => [154, 123]]);

        $this->logger->expects(self::never())
            ->method(self::anything());

        $this->fileSystemAdapter->expects(self::once())
            ->method('moveSitemaps')
            ->with([154, 123]);

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->getSession())
        );
    }

    public function testProcessWithExceptionDuringMovingOfTheFiles()
    {
        $websiteIds = [123, 234];
        $message = $this->getMessage(['version' => 1, 'websiteIds' => $websiteIds]);

        $exception = new \Exception('some error');

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during moving the generated sitemaps.',
                ['exception' => $exception]
            );

        $this->fileSystemAdapter->expects(self::once())
            ->method('moveSitemaps')
            ->with($websiteIds)
            ->willThrowException($exception);

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->getSession())
        );
    }
}
