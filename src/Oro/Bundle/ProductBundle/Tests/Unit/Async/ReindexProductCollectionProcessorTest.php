<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Async;

use Oro\Bundle\ProductBundle\Async\ReindexProductCollectionProcessor;
use Oro\Bundle\ProductBundle\Async\Topics;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\Exception\InvalidArgumentException;
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
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;

class ReindexProductCollectionProcessorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var JobRunner|\PHPUnit\Framework\MockObject\MockObject
     */
    private $jobRunner;

    /**
     * @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $producer;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var ReindexMessageGranularizer|\PHPUnit\Framework\MockObject\MockObject
     */
    private $reindexMessageGranularizer;

    /**
     * @var SegmentMessageFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $messageFactory;

    /**
     * @var SegmentSnapshotDeltaProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $productCollectionDeltaProvider;

    /**
     * @var ReindexProductCollectionProcessor
     */
    private $processor;

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

    public function testProcessWhenMessageIsInvalid()
    {
        $messageBody = ['some body item'];
        $message = $this->getMessage($messageBody);

        $exceptionMessage = 'Some exception message.';
        $exception = new InvalidArgumentException($exceptionMessage);
        $this->messageFactory->expects($this->once())
            ->method('getSegmentFromMessage')
            ->with($messageBody)
            ->willThrowException($exception);
        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Queue Message is invalid',
                [
                    'exception' => $exception
                ]
            );

        $result = $this->processor->process($message, $this->getSession());
        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testProcessWhenUnexpectedExceptionOccurred()
    {
        $messageBody = ['some body item'];
        $message = $this->getMessage($messageBody);

        $exceptionMessage = 'Some exception message.';
        $exception = new \Exception($exceptionMessage);
        $this->messageFactory->expects($this->once())
            ->method('getSegmentFromMessage')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during segment product collection reindexation',
                [
                    'topic' => Topics::REINDEX_PRODUCT_COLLECTION_BY_SEGMENT,
                    'exception' => $exception,
                ]
            );

        $result = $this->processor->process($message, $this->getSession());
        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testProcess()
    {
        $messageBody = ['some body item'];
        $isFull = false;
        $this->messageFactory->expects($this->once())
            ->method('getIsFull')
            ->with($messageBody)
            ->willReturn($isFull);

        /** @var Segment $segment */
        $segment = $this->getEntity(Segment::class, ['id' => 2]);
        $message = $this->getMessage($messageBody);
        $websiteIds = [777, 1];
        $this->expectedMessageFactory($messageBody, $segment, $websiteIds);

        $addedProductsId = [2, 3];
        $this->productCollectionDeltaProvider->expects($this->once())
            ->method('getAddedEntityIds')
            ->with($segment)
            ->willReturn($this->createGenerator($addedProductsId));
        $removedProductIds = [7];
        $this->productCollectionDeltaProvider->expects($this->once())
            ->method('getRemovedEntityIds')
            ->with($segment)
            ->willReturn($this->createGenerator($removedProductIds));

        $messageOne = [
            'class'   => [Product::class],
            'context' => ['websiteIds' => $websiteIds, 'entityIds' => $addedProductsId]
        ];
        $messageTwo = [
            'class'   => [Product::class],
            'context' => ['websiteIds' => $websiteIds, 'entityIds' => $removedProductIds]
        ];
        $this->reindexMessageGranularizer->expects($this->exactly(2))
            ->method('process')
            ->withConsecutive(
                [[Product::class], $websiteIds, [AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => $addedProductsId]],
                [[Product::class], $websiteIds, [AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => $removedProductIds]]
            )
            ->willReturnOnConsecutiveCalls(
                [$messageOne],
                [$messageTwo]
            );

        $expectedJobName = $this->getExpectedJobName($message, $segment);
        $this->expectedRunUnique($expectedJobName);

        $i = 5;
        $this->jobRunner->expects($this->exactly(2))
            ->method('createDelayed')
            ->withConsecutive(
                [$expectedJobName . ':reindex:1'],
                [$expectedJobName . ':reindex:2']
            )
            ->willReturnCallback(function ($name, $callback) use (&$i) {
                $delayedJob = $this->getEntity(Job::class, ['id' => ++$i]);
                return $callback($this->jobRunner, $delayedJob);
            });
        $this->producer->expects($this->exactly(2))
            ->method('send')
            ->withConsecutive(
                [
                    AsyncIndexer::TOPIC_REINDEX,
                    new Message(array_merge($messageOne, ['jobId' => 6]), AsyncIndexer::DEFAULT_PRIORITY_REINDEX)
                ],
                [
                    AsyncIndexer::TOPIC_REINDEX,
                    new Message(array_merge($messageTwo, ['jobId' => 7]), AsyncIndexer::DEFAULT_PRIORITY_REINDEX)
                ]
            );

        $result = $this->processor->process($message, $this->getSession());
        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessWithWithManuallyAdded()
    {
        $messageBody = ['some body item'];
        $isFull = false;
        $this->messageFactory->expects($this->once())
            ->method('getIsFull')
            ->with($messageBody)
            ->willReturn($isFull);

        $definition = 'some definition';
        /** @var Segment $segment */
        $segment = $this->getEntity(Segment::class, ['id' => 2, 'definition' => $definition]);
        $message = $this->getMessage($messageBody);
        $websiteIds = [777, 1];
        $this->expectedMessageFactory($messageBody, $segment, $websiteIds);

        $addedProductsId = [2, 3];
        $this->productCollectionDeltaProvider->expects($this->once())
            ->method('getAddedEntityIds')
            ->with($segment)
            ->willReturn($this->createGenerator($addedProductsId));
        $removedProductIds = [7];
        $this->productCollectionDeltaProvider->expects($this->once())
            ->method('getRemovedEntityIds')
            ->with($segment)
            ->willReturn($this->createGenerator($removedProductIds));

        $additionalProductIds = [3, 4];
        $this->messageFactory->expects($this->once())
            ->method('getAdditionalProductsFromMessage')
            ->with($messageBody)
            ->willReturn($additionalProductIds);

        $messageOne = [
            'class'   => [Product::class],
            'context' => ['websiteIds' => $websiteIds, 'entityIds' => $addedProductsId]
        ];
        $messageTwo = [
            'class'   => [Product::class],
            'context' => ['websiteIds' => $websiteIds, 'entityIds' => $removedProductIds]
        ];
        $messageThree = [
            'class'   => [Product::class],
            'context' => ['websiteIds' => $websiteIds, 'entityIds' => [1 => 4]]
        ];
        $this->reindexMessageGranularizer->expects($this->exactly(3))
            ->method('process')
            ->withConsecutive(
                [[Product::class], $websiteIds, [AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => $addedProductsId]],
                [[Product::class], $websiteIds, [AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => $removedProductIds]],
                [
                    [Product::class],
                    $websiteIds,
                    [AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1 => 4]]
                ]
            )
            ->willReturnOnConsecutiveCalls(
                [$messageOne],
                [$messageTwo],
                [$messageThree]
            );

        $expectedJobName = $this->getExpectedJobName($message, $segment);
        $this->expectedRunUnique($expectedJobName);

        $i = 5;
        $this->jobRunner->expects($this->exactly(3))
            ->method('createDelayed')
            ->withConsecutive(
                [$expectedJobName . ':reindex:1'],
                [$expectedJobName . ':reindex:2'],
                [$expectedJobName . ':reindex:3']
            )
            ->willReturnCallback(function ($name, $callback) use (&$i) {
                $delayedJob = $this->getEntity(Job::class, ['id' => ++$i]);
                return $callback($this->jobRunner, $delayedJob);
            });
        $this->producer->expects($this->exactly(3))
            ->method('send')
            ->withConsecutive(
                [
                    AsyncIndexer::TOPIC_REINDEX,
                    new Message(array_merge($messageOne, ['jobId' => 6]), AsyncIndexer::DEFAULT_PRIORITY_REINDEX)
                ],
                [
                    AsyncIndexer::TOPIC_REINDEX,
                    new Message(array_merge($messageTwo, ['jobId' => 7]), AsyncIndexer::DEFAULT_PRIORITY_REINDEX)
                ],
                [
                    AsyncIndexer::TOPIC_REINDEX,
                    new Message(array_merge($messageThree, ['jobId' => 8]), AsyncIndexer::DEFAULT_PRIORITY_REINDEX)
                ]
            );

        $result = $this->processor->process($message, $this->getSession());
        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessWhenNoSegmentId()
    {
        $messageBody = ['some body item'];
        $isFull = false;
        $this->messageFactory->expects($this->once())
            ->method('getIsFull')
            ->with($messageBody)
            ->willReturn($isFull);

        /** @var Segment $segment */
        $segment = $this->getEntity(Segment::class, ['id' => null]);
        $message = $this->getMessage($messageBody);
        $websiteIds = [777, 1];
        $this->expectedMessageFactory($messageBody, $segment, $websiteIds);

        $addedProductsId = [2, 3];
        $this->productCollectionDeltaProvider->expects($this->once())
            ->method('getAddedEntityIds')
            ->with($segment)
            ->willReturn($this->createGenerator($addedProductsId));
        $this->productCollectionDeltaProvider->expects($this->never())
            ->method('getRemovedEntityIds');

        $expectedJobName = $this->getExpectedJobName($message, $segment);
        $this->expectedSendForOneBatch($expectedJobName, $websiteIds, $addedProductsId);

        $result = $this->processor->process($message, $this->getSession());
        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessWhenNoSegmentIdAndIsFullTrue()
    {
        $messageBody = ['some body item'];
        $isFull = true;
        $this->messageFactory->expects($this->once())
            ->method('getIsFull')
            ->with($messageBody)
            ->willReturn($isFull);

        /** @var Segment $segment */
        $segment = $this->getEntity(Segment::class, ['id' => null]);
        $message = $this->getMessage($messageBody);
        $websiteIds = [777, 1];
        $this->expectedMessageFactory($messageBody, $segment, $websiteIds);

        $addedProductsId = [2, 3, 4];
        $this->productCollectionDeltaProvider->expects($this->once())
            ->method('getAllEntityIds')
            ->with($segment)
            ->willReturn($this->createGenerator($addedProductsId));
        $this->productCollectionDeltaProvider->expects($this->never())
            ->method('getAddedEntityIds');
        $this->productCollectionDeltaProvider->expects($this->never())
            ->method('getRemovedEntityIds');

        $expectedJobName = $this->getExpectedJobName($message, $segment);
        $this->expectedSendForOneBatch($expectedJobName, $websiteIds, $addedProductsId);

        $result = $this->processor->process($message, $this->getSession());
        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testGetSubscribedTopics()
    {
        $this->assertEquals(
            [Topics::REINDEX_PRODUCT_COLLECTION_BY_SEGMENT],
            ReindexProductCollectionProcessor::getSubscribedTopics()
        );
    }

    private function expectedMessageFactory(array $messageBody, Segment $segment, array $websiteIds)
    {
        $this->messageFactory->expects($this->once())
            ->method('getSegmentFromMessage')
            ->with($messageBody)
            ->willReturn($segment);
        $this->messageFactory->expects($this->once())
            ->method('getWebsiteIdsFromMessage')
            ->with($messageBody)
            ->willReturn($websiteIds);
    }

    /**
     * @param MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message
     * @param Segment $segment
     * @return string
     */
    private function getExpectedJobName(MessageInterface $message, Segment $segment)
    {
        $expectedJobName = Topics::REINDEX_PRODUCT_COLLECTION_BY_SEGMENT
            . ':' . md5($segment->getDefinition()) . ':' . md5(implode([1, 777]));
        $message->expects($this->any())
            ->method('getMessageId')
            ->willReturn('msg-001');

        return $expectedJobName;
    }

    /**
     * @param string $expectedJobName
     */
    private function expectedRunUnique($expectedJobName)
    {
        /** @var Job $job */
        $job = $this->getEntity(Job::class, ['id' => 1]);
        $childJob = $this->getEntity(Job::class, ['id' => 2, 'rootJob' => $job, 'name' => $expectedJobName]);
        $this->jobRunner->expects($this->once())
            ->method('runUnique')
            ->with('msg-001', $expectedJobName)
            ->willReturnCallback(function ($jobId, $name, $callback) use ($childJob) {
                return $callback($this->jobRunner, $childJob);
            });
    }

    /**
     * @param string $expectedJobName
     * @param array $websiteIds
     * @param array $productIds
     */
    private function expectedSendForOneBatch($expectedJobName, array $websiteIds, array $productIds)
    {
        $messageOne = [
            'class'   => [Product::class],
            'context' => ['websiteIds' => $websiteIds, 'entityIds' => $productIds]
        ];
        $this->reindexMessageGranularizer->expects($this->once())
            ->method('process')
            ->with([Product::class], $websiteIds, [AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => $productIds])
            ->willReturn([$messageOne]);

        $this->expectedRunUnique($expectedJobName);

        $i = 5;
        $this->jobRunner->expects($this->once())
            ->method('createDelayed')
            ->with($expectedJobName . ':reindex:1')
            ->willReturnCallback(function ($name, $callback) use (&$i) {
                $delayedJob = $this->getEntity(Job::class, ['id' => ++$i]);
                return $callback($this->jobRunner, $delayedJob);
            });
        $this->producer->expects($this->once())
            ->method('send')
            ->with(
                AsyncIndexer::TOPIC_REINDEX,
                new Message(array_merge($messageOne, ['jobId' => 6]), AsyncIndexer::DEFAULT_PRIORITY_REINDEX)
            );
    }

    /**
     * @param array $body
     * @return MessageInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getMessage(array $body = [])
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(json_encode($body));

        return $message;
    }

    /**
     * @return SessionInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getSession()
    {
        return $this->createMock(SessionInterface::class);
    }

    /**
     * @param array $productIds
     * @return \Generator
     */
    private function createGenerator(array $productIds)
    {
        foreach ($productIds as &$productId) {
            $productId = ['id' => $productId];
        }
        unset($productId);

        yield $productIds;
    }
}
