<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Async;

use Oro\Bundle\ProductBundle\Async\ReindexProductCollectionProcessor;
use Oro\Bundle\ProductBundle\Async\Topic\ReindexProductCollectionBySegmentTopic;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\ProductBundle\Model\SegmentMessageFactory;
use Oro\Bundle\ProductBundle\Storage\ProductWebsiteReindexRequestDataStorageInterface;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Provider\SegmentSnapshotDeltaProvider;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class ReindexProductCollectionProcessorTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $session;

    /** @var JobRunner|\PHPUnit\Framework\MockObject\MockObject */
    private $jobRunner;

    /** @var SegmentMessageFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $messageFactory;

    /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $message;

    /** @var ReindexProductCollectionProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->session = $this->createMock(SessionInterface::class);
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->messageFactory = $this->createMock(SegmentMessageFactory::class);
        $segmentSnapshotDeltaProvider = $this->createMock(SegmentSnapshotDeltaProvider::class);
        $websiteReindexRequestDataStorage = $this->createMock(
            ProductWebsiteReindexRequestDataStorageInterface::class
        );
        $this->message = $this->createMock(MessageInterface::class);

        $this->processor = new ReindexProductCollectionProcessor(
            $this->jobRunner,
            $this->messageFactory,
            $segmentSnapshotDeltaProvider,
            $websiteReindexRequestDataStorage
        );

        $this->setUpLoggerMock($this->processor);
    }

    public function testProcess(): void
    {
        $body = $this->getMessageBody();

        $this->message->expects(self::once())
            ->method('getBody')
            ->willReturn($body);
        $this->message->expects(self::once())
            ->method('getMessageId')
            ->willReturn((string)$body[ReindexProductCollectionBySegmentTopic::OPTION_NAME_JOB_ID]);

        $this->messageFactory->expects(self::once())
            ->method('getJobIdFromMessage')
            ->with($body)
            ->willReturn($body[ReindexProductCollectionBySegmentTopic::OPTION_NAME_JOB_ID]);
        $this->messageFactory->expects(self::once())
            ->method('getSegmentFromMessage')
            ->with($body)
            ->willReturn($this->getSegment($body));
        $this->messageFactory->expects(self::once())
            ->method('getWebsiteIdsFromMessage')
            ->with($body)
            ->willReturn($body[ReindexProductCollectionBySegmentTopic::OPTION_NAME_WEBSITE_IDS]);
        $this->messageFactory->expects(self::once())
            ->method('getIsFull')
            ->with($body)
            ->willReturn($body[ReindexProductCollectionBySegmentTopic::OPTION_NAME_IS_FULL]);
        $this->messageFactory->expects(self::once())
            ->method('getAdditionalProductsFromMessage')
            ->with($body)
            ->willReturn($body[ReindexProductCollectionBySegmentTopic::OPTION_NAME_ADDITIONAL_PRODUCTS]);

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->willReturnCallback(function (int $jobId, callable $callback) {
                self::assertEquals($this->message->getMessageId(), (string)$jobId);

                return $callback($this->jobRunner, $this->getJob());
            });

        $result = $this->processor->process($this->message, $this->session);

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessWithInvalidJobResult(): void
    {
        $body = $this->getMessageBody();

        $this->message->expects(self::once())
            ->method('getBody')
            ->willReturn($body);

        $this->messageFactory->expects(self::once())
            ->method('getJobIdFromMessage')
            ->with($body)
            ->willReturn($body[ReindexProductCollectionBySegmentTopic::OPTION_NAME_JOB_ID]);
        $this->messageFactory->expects(self::once())
            ->method('getSegmentFromMessage')
            ->with($body)
            ->willReturn($this->getSegment($body));
        $this->messageFactory->expects(self::once())
            ->method('getWebsiteIdsFromMessage')
            ->with($body)
            ->willReturn($body[ReindexProductCollectionBySegmentTopic::OPTION_NAME_WEBSITE_IDS]);
        $this->messageFactory->expects(self::once())
            ->method('getIsFull')
            ->with($body)
            ->willReturn($body[ReindexProductCollectionBySegmentTopic::OPTION_NAME_IS_FULL]);
        $this->messageFactory->expects(self::once())
            ->method('getAdditionalProductsFromMessage')
            ->with($body)
            ->willReturn($body[ReindexProductCollectionBySegmentTopic::OPTION_NAME_ADDITIONAL_PRODUCTS]);

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->willReturn(false);

        $result = $this->processor->process($this->message, $this->session);

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testProcessWithException(): void
    {
        $body = $this->getMessageBody();

        $this->message->expects(self::once())
            ->method('getBody')
            ->willReturn($body);

        $this->messageFactory->expects(self::once())
            ->method('getJobIdFromMessage')
            ->with($body)
            ->willThrowException(new InvalidArgumentException());

        $this->assertLoggerErrorMethodCalled();

        $result = $this->processor->process($this->message, $this->session);

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testProcessWithInvalidArgumentException(): void
    {
        $body = $this->getMessageBody();

        $this->message->expects(self::once())
            ->method('getBody')
            ->willReturn($body);

        $this->messageFactory->expects(self::once())
            ->method('getSegmentFromMessage')
            ->with($body)
            ->willReturn($this->getSegment($body));
        $this->messageFactory->expects(self::once())
            ->method('getWebsiteIdsFromMessage')
            ->with($body)
            ->willReturn($body[ReindexProductCollectionBySegmentTopic::OPTION_NAME_WEBSITE_IDS]);
        $this->messageFactory->expects(self::once())
            ->method('getIsFull')
            ->with($body)
            ->willReturn($body[ReindexProductCollectionBySegmentTopic::OPTION_NAME_IS_FULL]);
        $this->messageFactory->expects(self::once())
            ->method('getAdditionalProductsFromMessage')
            ->with($body)
            ->willReturn($body[ReindexProductCollectionBySegmentTopic::OPTION_NAME_ADDITIONAL_PRODUCTS]);

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->willThrowException(new \Exception());

        $this->assertLoggerErrorMethodCalled();

        $result = $this->processor->process($this->message, $this->session);

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    private function getJob(): Job
    {
        $body = $this->getMessageBody();
        $rootJob = new Job();
        $job = new Job();
        $job->setId($body[ReindexProductCollectionBySegmentTopic::OPTION_NAME_JOB_ID]);
        $job->setRootJob($rootJob);

        return $job;
    }

    private function getMessageBody(): array
    {
        return [
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_ID => 1,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_JOB_ID => 2,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_WEBSITE_IDS => [1],
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_DEFINITION => null,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_IS_FULL => true,
            ReindexProductCollectionBySegmentTopic::OPTION_NAME_ADDITIONAL_PRODUCTS => [],
        ];
    }

    private function getSegment(array $data): Segment
    {
        $segment = new Segment();
        $segment->setEntity(Product::class);
        $segment->setDefinition($data[ReindexProductCollectionBySegmentTopic::OPTION_NAME_DEFINITION]);

        return $segment;
    }
}
