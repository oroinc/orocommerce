<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Async;

use Oro\Bundle\ProductBundle\Async\ReindexRequestItemProductsByRelatedJobProcessor;
use Oro\Bundle\ProductBundle\Async\Topic\ReindexRequestItemProductsByRelatedJobIdTopic;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Storage\ProductWebsiteReindexRequestDataStorageInterface;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchReindexTopic;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class ReindexRequestItemProductsByRelatedJobProcessorTest extends \PHPUnit\Framework\TestCase
{
    private ProductWebsiteReindexRequestDataStorageInterface|MockObject $websiteReindexRequestDataStorage;

    private JobRunner|MockObject $jobRunner;

    private MessageProducerInterface|MockObject $messageProducer;

    private ReindexRequestItemProductsByRelatedJobProcessor $processor;

    private MessageInterface|MockObject $message;

    private SessionInterface|MockObject $session;

    private LoggerInterface|MockObject $loggerMock;

    public function setUp(): void
    {
        $this->websiteReindexRequestDataStorage = $this
            ->createMock(ProductWebsiteReindexRequestDataStorageInterface::class);

        $this->jobRunner = $this->createMock(JobRunner::class);

        $this->messageProducer = $this->createMock(MessageProducerInterface::class);

        $this->message = $this->createMock(MessageInterface::class);

        $this->session = $this->createMock(SessionInterface::class);

        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->processor = new ReindexRequestItemProductsByRelatedJobProcessor(
            $this->websiteReindexRequestDataStorage,
            $this->jobRunner,
            $this->messageProducer,
            $this->loggerMock
        );
    }

    public function testProcessWithEmptyWebsiteIds(): void
    {
        $this->message
            ->expects(self::once())
            ->method('getBody')
            ->willReturn([
                'relatedJobId' => 1,
                'indexationFieldsGroups' => ['main'],
            ]);

        $this->websiteReindexRequestDataStorage
            ->expects(self::once())
            ->method('getWebsiteIdsByRelatedJobId')
            ->willReturn([]);

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->message, $this->session)
        );
    }

    public function testProcessWithWebsiteIds(): void
    {
        $jobId = 1;
        $websiteIds = [1];
        $productIds = [[2, 3]];
        $fieldGroups = ['main'];

        $this->message
            ->expects(self::once())
            ->method('getBody')
            ->willReturn([
                'relatedJobId' => $jobId,
                'indexationFieldsGroups' => $fieldGroups,
            ]);

        $this->websiteReindexRequestDataStorage
            ->expects(self::once())
            ->method('getWebsiteIdsByRelatedJobId')
            ->willReturn($websiteIds);

        $this->websiteReindexRequestDataStorage
            ->expects(self::once())
            ->method('getProductIdIteratorByRelatedJobIdAndWebsiteId')
            ->willReturn(new \ArrayIterator($productIds));

        $this->messageProducer
            ->expects(self::once())
            ->method('send')
            ->with(
                WebsiteSearchReindexTopic::getName(),
                [
                    'jobId' => $jobId,
                    'class' => Product::class,
                    'context' => [
                        AbstractIndexer::CONTEXT_WEBSITE_IDS => $websiteIds,
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => $productIds[0],
                        AbstractIndexer::CONTEXT_FIELD_GROUPS => $fieldGroups,
                    ],
                ]
            );

        $this->jobRunner->method('createDelayed')
            ->willReturnCallback(
                function (string $job, callable $callback) use ($jobId) {
                    $job = new Job();
                    $job->setId($jobId);
                    return $callback($this->jobRunner, $job);
                }
            );

        $this->jobRunner
            ->expects(self::once())
            ->method('runUnique')
            ->willReturnCallback(
                function (string $ownerId, string $rootJobName, callable $callback) {
                    self::assertEquals($this->message->getMessageId(), $ownerId);
                    self::assertStringContainsString(
                        ReindexRequestItemProductsByRelatedJobIdTopic::getName(),
                        $rootJobName
                    );

                    return $callback($this->jobRunner, new Job());
                }
            );

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->message, $this->session)
        );
    }

    public function testProcessWithJobRunnerFailedResult(): void
    {
        $this->message
            ->expects(self::once())
            ->method('getBody')
            ->willReturn([
                'relatedJobId' => 1,
                'indexationFieldsGroups' => ['main'],
            ]);

        $this->websiteReindexRequestDataStorage
            ->expects(self::once())
            ->method('getWebsiteIdsByRelatedJobId')
            ->willReturn([1, 2, 3]);

        $this->jobRunner
            ->expects(self::once())
            ->method('runUnique')
            ->willReturn(null);

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->message, $this->session)
        );
    }

    public function testProcessWithJobRunnerThrowException(): void
    {
        $exception = new \Exception();

        $this->message
            ->expects(self::once())
            ->method('getBody')
            ->willReturn([
                'relatedJobId' => 1,
                'indexationFieldsGroups' => ['main'],
            ]);

        $this->websiteReindexRequestDataStorage
            ->expects(self::once())
            ->method('getWebsiteIdsByRelatedJobId')
            ->willReturn([1, 2, 3]);

        $this->jobRunner
            ->expects(self::once())
            ->method('runUnique')
            ->willThrowException($exception);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during queue message processing',
                [
                    'exception' => $exception,
                    'topic' => ReindexRequestItemProductsByRelatedJobIdTopic::getName(),
                ]
            );

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->message, $this->session)
        );
    }
}
