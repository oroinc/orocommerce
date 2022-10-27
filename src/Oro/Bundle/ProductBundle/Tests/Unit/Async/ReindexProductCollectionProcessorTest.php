<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Async;

use Oro\Bundle\ProductBundle\Async\ReindexProductCollectionProcessor;
use Oro\Bundle\ProductBundle\Async\Topic\ReindexProductCollectionBySegmentTopic;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\SegmentMessageFactory;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Provider\SegmentSnapshotDeltaProvider;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncMessaging\ReindexMessageGranularizer;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Test\JobRunner;
use Oro\Component\MessageQueue\Transport\Message as TransportMessage;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\ReflectionUtil;
use Psr\Log\LoggerInterface;

class ReindexProductCollectionProcessorTest extends \PHPUnit\Framework\TestCase
{
    private JobRunner|\PHPUnit\Framework\MockObject\MockObject $jobRunner;

    private MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject $producer;

    private LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger;

    private ReindexMessageGranularizer|\PHPUnit\Framework\MockObject\MockObject $reindexMessageGranularizer;

    private SegmentMessageFactory|\PHPUnit\Framework\MockObject\MockObject $messageFactory;

    private SegmentSnapshotDeltaProvider|\PHPUnit\Framework\MockObject\MockObject $productCollectionDeltaProvider;

    private ReindexProductCollectionProcessor $processor;

    protected function setUp(): void
    {
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->reindexMessageGranularizer = $this->createMock(ReindexMessageGranularizer::class);
        $this->messageFactory = $this->createMock(SegmentMessageFactory::class);
        $this->productCollectionDeltaProvider = $this->createMock(SegmentSnapshotDeltaProvider::class);

        $this->processor = new ReindexProductCollectionProcessor(
            $this->jobRunner,
            $this->producer,
            $this->logger,
            $this->reindexMessageGranularizer,
            $this->messageFactory,
            $this->productCollectionDeltaProvider
        );
    }

    public function testProcessWhenUnexpectedExceptionOccurred(): void
    {
        $messageBody = [
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_ID => PHP_INT_MAX,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_WEBSITE_IDS => [PHP_INT_MAX],
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_IS_FULL => false,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_ADDITIONAL_PRODUCTS => [],
        ];
        $message = $this->getMessage($messageBody);

        $exceptionMessage = 'Some exception message.';
        $exception = new \Exception($exceptionMessage);
        $this->messageFactory->expects(self::once())
            ->method('getSegment')
            ->with($messageBody)
            ->willThrowException($exception);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during segment product collection reindexation',
                [
                    'topic' => ReindexProductCollectionBySegmentTopic::getName(),
                    'exception' => $exception,
                ]
            );

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));
        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testProcess(): void
    {
        $websiteIds = [777, 1];
        $id = 2;
        $messageBody = [
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_ID => $id,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_WEBSITE_IDS => $websiteIds,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_IS_FULL => false,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_ADDITIONAL_PRODUCTS => [],
        ];

        $message = $this->getMessage($messageBody);
        $segment = $this->getSegment($id);

        $this->expectedMessageFactory($messageBody, $segment);

        $addedProductsId = [2, 3];
        $this->productCollectionDeltaProvider->expects(self::once())
            ->method('getAddedEntityIds')
            ->with($segment)
            ->willReturn($this->createGenerator($addedProductsId));
        $removedProductIds = [7];
        $this->productCollectionDeltaProvider->expects(self::once())
            ->method('getRemovedEntityIds')
            ->with($segment)
            ->willReturn($this->createGenerator($removedProductIds));

        $messageOne = [
            'class' => [Product::class],
            'context' => ['websiteIds' => $websiteIds, 'entityIds' => $addedProductsId],
        ];
        $messageTwo = [
            'class' => [Product::class],
            'context' => ['websiteIds' => $websiteIds, 'entityIds' => $removedProductIds],
        ];
        $this->reindexMessageGranularizer->expects(self::exactly(2))
            ->method('process')
            ->withConsecutive(
                [[Product::class], $websiteIds, [AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => $addedProductsId]],
                [[Product::class], $websiteIds, [AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => $removedProductIds]]
            )
            ->willReturnOnConsecutiveCalls(
                [$messageOne],
                [$messageTwo]
            );

        $expectedJobName = $this->getExpectedJobName($segment);
        $this->expectedRunUnique($expectedJobName);

        $i = 5;
        $this->jobRunner->expects(self::exactly(2))
            ->method('createDelayed')
            ->withConsecutive(
                [$expectedJobName . ':reindex:1'],
                [$expectedJobName . ':reindex:2']
            )
            ->willReturnCallback(function ($name, $callback) use (&$i) {
                $delayedJob = $this->getJob(++$i);
                return $callback($this->jobRunner, $delayedJob);
            });
        $this->producer->expects(self::exactly(2))
            ->method('send')
            ->withConsecutive(
                [
                    AsyncIndexer::TOPIC_REINDEX,
                    new Message(array_merge($messageOne, ['jobId' => 6]), AsyncIndexer::DEFAULT_PRIORITY_REINDEX),
                ],
                [
                    AsyncIndexer::TOPIC_REINDEX,
                    new Message(array_merge($messageTwo, ['jobId' => 7]), AsyncIndexer::DEFAULT_PRIORITY_REINDEX),
                ]
            );

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessWithWithManuallyAdded(): void
    {
        $websiteIds = [777, 1];
        $id = 2;
        $additionalProductIds = [3, 4];
        $messageBody = [
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_ID => $id,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_WEBSITE_IDS => $websiteIds,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_IS_FULL => false,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_ADDITIONAL_PRODUCTS => $additionalProductIds,
        ];

        $message = $this->getMessage($messageBody);
        $segment = $this->getSegment(2, 'some definition');

        $this->expectedMessageFactory($messageBody, $segment);

        $addedProductsId = [2, 3];
        $this->productCollectionDeltaProvider->expects(self::once())
            ->method('getAddedEntityIds')
            ->with($segment)
            ->willReturn($this->createGenerator($addedProductsId));
        $removedProductIds = [7];
        $this->productCollectionDeltaProvider->expects(self::once())
            ->method('getRemovedEntityIds')
            ->with($segment)
            ->willReturn($this->createGenerator($removedProductIds));

        $messageOne = [
            'class' => [Product::class],
            'context' => ['websiteIds' => $websiteIds, 'entityIds' => $addedProductsId],
        ];
        $messageTwo = [
            'class' => [Product::class],
            'context' => ['websiteIds' => $websiteIds, 'entityIds' => $removedProductIds],
        ];
        $messageThree = [
            'class' => [Product::class],
            'context' => ['websiteIds' => $websiteIds, 'entityIds' => [1 => 4]],
        ];
        $this->reindexMessageGranularizer->expects(self::exactly(3))
            ->method('process')
            ->withConsecutive(
                [[Product::class], $websiteIds, [AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => $addedProductsId]],
                [[Product::class], $websiteIds, [AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => $removedProductIds]],
                [
                    [Product::class],
                    $websiteIds,
                    [AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1 => 4]],
                ]
            )
            ->willReturnOnConsecutiveCalls(
                [$messageOne],
                [$messageTwo],
                [$messageThree]
            );

        $expectedJobName = $this->getExpectedJobName($segment);
        $this->expectedRunUnique($expectedJobName);

        $i = 5;
        $this->jobRunner->expects(self::exactly(3))
            ->method('createDelayed')
            ->withConsecutive(
                [$expectedJobName . ':reindex:1'],
                [$expectedJobName . ':reindex:2'],
                [$expectedJobName . ':reindex:3']
            )
            ->willReturnCallback(function ($name, $callback) use (&$i) {
                $delayedJob = $this->getJob(++$i);

                return $callback($this->jobRunner, $delayedJob);
            });
        $this->producer->expects(self::exactly(3))
            ->method('send')
            ->withConsecutive(
                [
                    AsyncIndexer::TOPIC_REINDEX,
                    new Message(array_merge($messageOne, ['jobId' => 6]), AsyncIndexer::DEFAULT_PRIORITY_REINDEX),
                ],
                [
                    AsyncIndexer::TOPIC_REINDEX,
                    new Message(array_merge($messageTwo, ['jobId' => 7]), AsyncIndexer::DEFAULT_PRIORITY_REINDEX),
                ],
                [
                    AsyncIndexer::TOPIC_REINDEX,
                    new Message(array_merge($messageThree, ['jobId' => 8]), AsyncIndexer::DEFAULT_PRIORITY_REINDEX),
                ]
            );

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessWhenNoSegmentId(): void
    {
        $websiteIds = [777, 1];
        $definition = 'definition';
        $messageBody = [
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_ID => null,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_WEBSITE_IDS => $websiteIds,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_IS_FULL => false,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_ADDITIONAL_PRODUCTS => [],
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_DEFINITION => $definition,
        ];

        $message = $this->getMessage($messageBody);
        $segment = $this->getSegment(null, $definition);

        $this->expectedMessageFactory($messageBody, $segment);

        $addedProductsId = [2, 3];
        $this->productCollectionDeltaProvider->expects(self::once())
            ->method('getAddedEntityIds')
            ->with($segment)
            ->willReturn($this->createGenerator($addedProductsId));
        $this->productCollectionDeltaProvider->expects(self::never())
            ->method('getRemovedEntityIds');

        $expectedJobName = $this->getExpectedJobName($segment);
        $this->expectedSendForOneBatch($expectedJobName, $websiteIds, $addedProductsId);

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessWhenNoSegmentIdAndIsFullTrue(): void
    {
        $websiteIds = [777, 1];
        $definition = 'definition';
        $messageBody = [
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_ID => null,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_WEBSITE_IDS => $websiteIds,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_IS_FULL => true,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_ADDITIONAL_PRODUCTS => [],
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_DEFINITION => $definition,
        ];

        $message = $this->getMessage($messageBody);
        $segment = $this->getSegment(null, $definition);

        $this->expectedMessageFactory($messageBody, $segment);

        $addedProductsId = [2, 3, 4];
        $this->productCollectionDeltaProvider->expects(self::once())
            ->method('getAllEntityIds')
            ->with($segment)
            ->willReturn($this->createGenerator($addedProductsId));
        $this->productCollectionDeltaProvider->expects(self::never())
            ->method('getAddedEntityIds');
        $this->productCollectionDeltaProvider->expects(self::never())
            ->method('getRemovedEntityIds');

        $expectedJobName = $this->getExpectedJobName($segment);
        $this->expectedSendForOneBatch($expectedJobName, $websiteIds, $addedProductsId);

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals(
            [ReindexProductCollectionBySegmentTopic::getName()],
            ReindexProductCollectionProcessor::getSubscribedTopics()
        );
    }

    private function expectedMessageFactory(array $messageBody, Segment $segment): void
    {
        $this->messageFactory->expects(self::once())
            ->method('getSegment')
            ->with($messageBody)
            ->willReturn($segment);
    }

    private function getExpectedJobName(Segment $segment): string
    {
        return ReindexProductCollectionBySegmentTopic::getName()
            . ':' . md5($segment->getDefinition()) . ':' . md5(implode([1, 777]));
    }

    private function expectedRunUnique(string $expectedJobName): void
    {
        $job = new Job();
        $job->setId(1);

        $childJob = new Job();
        $childJob->setId(2);
        $childJob->setRootJob($job);
        $childJob->setName($expectedJobName);

        $this->jobRunner->expects(self::once())
            ->method('runUnique')
            ->with('msg-001', $expectedJobName)
            ->willReturnCallback(function ($jobId, $name, $callback) use ($childJob) {
                return $callback($this->jobRunner, $childJob);
            });
    }

    private function expectedSendForOneBatch(string $expectedJobName, array $websiteIds, array $productIds): void
    {
        $messageOne = [
            'class' => [Product::class],
            'context' => ['websiteIds' => $websiteIds, 'entityIds' => $productIds],
        ];
        $this->reindexMessageGranularizer->expects(self::once())
            ->method('process')
            ->with([Product::class], $websiteIds, [AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => $productIds])
            ->willReturn([$messageOne]);

        $this->expectedRunUnique($expectedJobName);

        $i = 5;
        $this->jobRunner->expects(self::once())
            ->method('createDelayed')
            ->with($expectedJobName . ':reindex:1')
            ->willReturnCallback(function ($name, $callback) use (&$i) {
                $delayedJob = $this->getJob(++$i);
                return $callback($this->jobRunner, $delayedJob);
            });
        $this->producer->expects(self::once())
            ->method('send')
            ->with(
                AsyncIndexer::TOPIC_REINDEX,
                new Message(array_merge($messageOne, ['jobId' => 6]), AsyncIndexer::DEFAULT_PRIORITY_REINDEX)
            );
    }

    private function getMessage(array $body = []): MessageInterface
    {
        $message = new TransportMessage();
        $message->setBody($body);
        $message->setMessageId('msg-001');

        return $message;
    }

    private function createGenerator(array $productIds): \Generator
    {
        foreach ($productIds as &$productId) {
            $productId = ['id' => $productId];
        }
        unset($productId);

        yield $productIds;
    }

    private function getJob(int $id): Job
    {
        $job = new Job();
        $job->setId($id);

        return $job;
    }

    private function getSegment(int $id = null, string $definition = null): Segment
    {
        $segment = new Segment();
        ReflectionUtil::setId($segment, $id);
        $segment->setDefinition($definition);

        return $segment;
    }
}
