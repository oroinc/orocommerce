<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Async;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchReindexGranulizedTopic;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchReindexTopic;
use Oro\Bundle\WebsiteSearchBundle\Async\WebsiteSearchReindexGranulizedProcessor;
use Oro\Bundle\WebsiteSearchBundle\Async\WebsiteSearchReindexProcessor;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncMessaging\ReindexMessageGranularizer;
use Oro\Bundle\WebsiteSearchBundle\Event\BeforeReindexEvent;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class WebsiteSearchReindexProcessorTest extends \PHPUnit\Framework\TestCase
{
    private MessageProcessorInterface|\PHPUnit\Framework\MockObject\MockObject $delayedJobRunnerProcessor;

    private WebsiteSearchReindexGranulizedProcessor|\PHPUnit\Framework\MockObject\MockObject
        $reindexGranulizedProcessor;

    private ReindexMessageGranularizer|\PHPUnit\Framework\MockObject\MockObject $reindexMessageGranularizer;

    private MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject $messageProducer;

    private EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher;

    private WebsiteSearchReindexProcessor $processor;

    protected function setUp(): void
    {
        $this->reindexGranulizedProcessor = $this->createMock(WebsiteSearchReindexGranulizedProcessor::class);
        $this->delayedJobRunnerProcessor = $this->createMock(MessageProcessorInterface::class);
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->reindexMessageGranularizer = $this->createMock(ReindexMessageGranularizer::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->processor = new WebsiteSearchReindexProcessor(
            $this->delayedJobRunnerProcessor,
            $this->reindexGranulizedProcessor,
            $this->reindexMessageGranularizer,
            $this->messageProducer,
            $this->eventDispatcher
        );
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals([WebsiteSearchReindexTopic::getName()], $this->processor::getSubscribedTopics());
    }

    public function testProcessWhenJobId(): void
    {
        $message = new Message();
        $message->setBody(['jobId' => 42, 'class' => Product::class, 'context' => []]);
        $session = $this->createMock(SessionInterface::class);
        $status = MessageProcessorInterface::ACK;

        $this->delayedJobRunnerProcessor
            ->expects(self::once())
            ->method('process')
            ->with($message, $session)
            ->willReturn($status);

        self::assertEquals($status, $this->processor->process($message, $session));
    }

    public function testProcessWhenGranulizeAndNoChunks(): void
    {
        $message = new Message();
        $messageBody = [
            'class' => Product::class,
            'context' => [AbstractIndexer::CONTEXT_WEBSITE_IDS => [3, 2, 1]],
            'granulize' => true,
        ];
        $message->setBody($messageBody);
        $session = $this->createMock(SessionInterface::class);
        $status = MessageProcessorInterface::ACK;

        $this->delayedJobRunnerProcessor
            ->expects(self::never())
            ->method(self::anything());

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(
                new BeforeReindexEvent($messageBody['class'], $messageBody['context']),
                BeforeReindexEvent::EVENT_NAME
            );

        $this->reindexMessageGranularizer
            ->expects(self::once())
            ->method('process')
            ->with($messageBody['class'], $messageBody['context'][AbstractIndexer::CONTEXT_WEBSITE_IDS])
            ->willReturnCallback(static function (): \Generator {
                if (false) {
                    yield [];
                }
            });

        $this->reindexGranulizedProcessor
            ->expects(self::never())
            ->method(self::anything());

        self::assertEquals($status, $this->processor->process($message, $session));
    }

    public function testProcessWhenGranulizeAndSingleChunk(): void
    {
        $message = new Message();
        $messageBody = [
            'class' => Product::class,
            'context' => [AbstractIndexer::CONTEXT_WEBSITE_IDS => [3, 2, 1]],
            'granulize' => true,
        ];
        $message->setBody($messageBody);
        $session = $this->createMock(SessionInterface::class);
        $status = MessageProcessorInterface::ACK;

        $this->delayedJobRunnerProcessor
            ->expects(self::never())
            ->method(self::anything());

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(
                new BeforeReindexEvent($messageBody['class'], $messageBody['context']),
                BeforeReindexEvent::EVENT_NAME
            );

        $childMessageBody = [
            'class' => $messageBody['class'],
            'context' => [
                AbstractIndexer::CONTEXT_WEBSITE_IDS => [3],
                AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [42],
            ],
        ];

        $this->reindexMessageGranularizer
            ->expects(self::once())
            ->method('process')
            ->with($messageBody['class'], $messageBody['context'][AbstractIndexer::CONTEXT_WEBSITE_IDS])
            ->willReturnCallback(static function () use ($childMessageBody): \Generator {
                yield $childMessageBody;
            });

        $this->reindexGranulizedProcessor
            ->expects(self::once())
            ->method('doReindex')
            ->with(
                $childMessageBody['class'],
                array_merge($childMessageBody['context'], ['skip_pre_processing' => true])
            )
            ->willReturn($status);

        self::assertEquals($status, $this->processor->process($message, $session));
    }

    public function testProcessWhenGranulizeAndMultipleChunks(): void
    {
        $message = new Message();
        $messageBody = [
            'class' => Product::class,
            'context' => [AbstractIndexer::CONTEXT_WEBSITE_IDS => [3, 2, 1]],
            'granulize' => true,
        ];
        $message->setBody($messageBody);
        $session = $this->createMock(SessionInterface::class);
        $status = MessageProcessorInterface::ACK;

        $this->delayedJobRunnerProcessor
            ->expects(self::never())
            ->method(self::anything());

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(
                new BeforeReindexEvent($messageBody['class'], $messageBody['context']),
                BeforeReindexEvent::EVENT_NAME
            );

        $childMessages = [
            [
                'class' => $messageBody['class'],
                'context' => [
                    AbstractIndexer::CONTEXT_WEBSITE_IDS => [3],
                    AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [42],
                ],
            ],
            [
                'class' => $messageBody['class'],
                'context' => [
                    AbstractIndexer::CONTEXT_WEBSITE_IDS => [2],
                    AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [4242],
                ],
            ],
        ];

        $this->reindexMessageGranularizer
            ->expects(self::once())
            ->method('process')
            ->with($messageBody['class'], $messageBody['context'][AbstractIndexer::CONTEXT_WEBSITE_IDS])
            ->willReturnCallback(static function () use ($childMessages): \Generator {
                foreach ($childMessages as $childMessageBody) {
                    yield $childMessageBody;
                }
            });

        $this->reindexGranulizedProcessor
            ->expects(self::never())
            ->method(self::anything());

        $this->messageProducer
            ->expects(self::exactly(2))
            ->method('send')
            ->withConsecutive(
                [WebsiteSearchReindexGranulizedTopic::getName(), $childMessages[0]],
                [WebsiteSearchReindexGranulizedTopic::getName(), $childMessages[1]]
            );

        self::assertEquals($status, $this->processor->process($message, $session));
    }

    public function testProcessWhenNotGranulize(): void
    {
        $message = new Message();
        $messageBody = [
            'class' => Product::class,
            'context' => [AbstractIndexer::CONTEXT_WEBSITE_IDS => [3, 2, 1]],
            'granulize' => false,
        ];
        $message->setBody($messageBody);
        $session = $this->createMock(SessionInterface::class);
        $status = MessageProcessorInterface::ACK;

        $this->delayedJobRunnerProcessor
            ->expects(self::never())
            ->method(self::anything());

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(
                new BeforeReindexEvent($messageBody['class'], $messageBody['context']),
                BeforeReindexEvent::EVENT_NAME
            );

        $this->reindexMessageGranularizer
            ->expects(self::never())
            ->method(self::anything());

        $this->reindexGranulizedProcessor
            ->expects(self::once())
            ->method('doReindex')
            ->with(
                $messageBody['class'],
                array_merge($messageBody['context'], ['skip_pre_processing' => true])
            )
            ->willReturn($status);

        self::assertEquals($status, $this->processor->process($message, $session));
    }
}
