<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Async;

use Monolog\Logger;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchDeleteTopic;
use Oro\Bundle\WebsiteSearchBundle\Async\WebsiteSearchDeleteProcessor;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\SearchProcessingEngineExceptionEvent;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class WebsiteSearchDeleteProcessorTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private IndexerInterface|\PHPUnit\Framework\MockObject\MockObject $indexer;

    private EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher;

    private WebsiteSearchDeleteProcessor $processor;

    protected function setUp(): void
    {
        $this->indexer = $this->createMock(IndexerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->processor = new WebsiteSearchDeleteProcessor($this->indexer, $this->eventDispatcher);

        $this->setUpLoggerMock($this->processor);
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals([WebsiteSearchDeleteTopic::getName()], $this->processor::getSubscribedTopics());
    }

    public function testProcess(): void
    {
        $message = new Message();
        $messageBody = ['entity' => new Product(), 'context' => [AbstractIndexer::CONTEXT_WEBSITE_IDS => [3, 2, 1]]];
        $message->setBody($messageBody);
        $session = $this->createMock(SessionInterface::class);

        $this->indexer
            ->expects(self::once())
            ->method('delete')
            ->with($messageBody['entity'], $messageBody['context']);

        $this->eventDispatcher
            ->expects(self::never())
            ->method(self::anything());

        self::assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $session));
    }

    public function testProcessWhenExceptionNotRetryable(): void
    {
        $message = new Message();
        $messageBody = ['entity' => new Product(), 'context' => [AbstractIndexer::CONTEXT_WEBSITE_IDS => [3, 2, 1]]];
        $message->setBody($messageBody);
        $session = $this->createMock(SessionInterface::class);

        $exception = new \RuntimeException('Sample exception');
        $this->indexer
            ->expects(self::once())
            ->method('delete')
            ->with($messageBody['entity'], $messageBody['context'])
            ->willThrowException($exception);

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(
                new SearchProcessingEngineExceptionEvent($exception),
                SearchProcessingEngineExceptionEvent::EVENT_NAME
            );

        $this->loggerMock
            ->expects(self::once())
            ->method('log')
            ->with(
                Logger::ERROR,
                'An unexpected exception occurred while working with search index. Error: {message}',
                [
                    'exception' => $exception,
                    'message' => $exception->getMessage(),
                ]
            );

        self::assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $session));
    }

    public function testProcessWhenExceptionRetryable(): void
    {
        $message = new Message();
        $messageBody = ['entity' => new Product(), 'context' => [AbstractIndexer::CONTEXT_WEBSITE_IDS => [3, 2, 1]]];
        $message->setBody($messageBody);
        $session = $this->createMock(SessionInterface::class);

        $exception = new \RuntimeException('Sample exception');
        $this->indexer
            ->expects(self::once())
            ->method('delete')
            ->with($messageBody['entity'], $messageBody['context'])
            ->willThrowException($exception);

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(
                new SearchProcessingEngineExceptionEvent($exception),
                SearchProcessingEngineExceptionEvent::EVENT_NAME
            )
            ->willReturnCallback(static function (SearchProcessingEngineExceptionEvent $event) {
                $event->setIsRetryable(true);

                return $event;
            });

        $this->loggerMock
            ->expects(self::once())
            ->method('log')
            ->with(
                Logger::WARNING,
                'An unexpected exception occurred while working with search index. Error: {message}',
                [
                    'exception' => $exception,
                    'message' => $exception->getMessage(),
                ]
            );

        self::assertEquals(MessageProcessorInterface::REQUEUE, $this->processor->process($message, $session));
    }
}
