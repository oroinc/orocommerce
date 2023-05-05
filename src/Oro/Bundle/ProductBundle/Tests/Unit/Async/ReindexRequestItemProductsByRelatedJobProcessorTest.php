<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Async;

use Oro\Bundle\ProductBundle\Async\ReindexRequestItemProductsByRelatedJobProcessor;
use Oro\Bundle\ProductBundle\Async\Topic\ReindexRequestItemProductsByRelatedJobIdTopic;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Storage\ProductWebsiteReindexRequestDataStorageInterface;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchReindexTopic;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class ReindexRequestItemProductsByRelatedJobProcessorTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    /** @var ProductWebsiteReindexRequestDataStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteReindexRequestDataStorage;

    /** @var JobRunner|\PHPUnit\Framework\MockObject\MockObject */
    private $jobRunner;

    /** @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $messageProducer;

    /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $message;

    /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $session;

    /** @var ReindexRequestItemProductsByRelatedJobProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->websiteReindexRequestDataStorage = $this->createMock(
            ProductWebsiteReindexRequestDataStorageInterface::class
        );
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->message = $this->createMock(MessageInterface::class);
        $this->session = $this->createMock(SessionInterface::class);

        $this->processor = new ReindexRequestItemProductsByRelatedJobProcessor(
            $this->websiteReindexRequestDataStorage,
            $this->jobRunner,
            $this->messageProducer
        );

        $this->setUpLoggerMock($this->processor);
    }

    public function testProcessWithEmptyWebsiteIds(): void
    {
        $this->message->expects(self::once())
            ->method('getBody')
            ->willReturn([
                'relatedJobId' => 1,
                'indexationFieldsGroups' => ['main'],
            ]);

        $this->websiteReindexRequestDataStorage->expects(self::once())
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

        $this->message->expects(self::once())
            ->method('getBody')
            ->willReturn([
                'relatedJobId' => $jobId,
                'indexationFieldsGroups' => $fieldGroups,
            ]);

        $this->websiteReindexRequestDataStorage->expects(self::once())
            ->method('getWebsiteIdsByRelatedJobId')
            ->willReturn($websiteIds);

        $this->websiteReindexRequestDataStorage->expects(self::once())
            ->method('getProductIdIteratorByRelatedJobIdAndWebsiteId')
            ->willReturn(new \ArrayIterator($productIds));

        $this->messageProducer->expects(self::once())
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

        $this->jobRunner->expects(self::once())
            ->method('createDelayed')
            ->willReturnCallback(function (string $job, callable $callback) use ($jobId) {
                $job = new Job();
                $job->setId($jobId);

                return $callback($this->jobRunner, $job);
            });

        $this->jobRunner->expects(self::once())
            ->method('runUniqueByMessage')
            ->willReturnCallback(function ($actualMessage, callable $callback) {
                self::assertEquals($actualMessage, $this->message);

                return $callback($this->jobRunner, new Job());
            });

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->message, $this->session)
        );
    }

    public function testProcessWithJobRunnerFailedResult(): void
    {
        $this->message->expects(self::once())
            ->method('getBody')
            ->willReturn([
                'relatedJobId' => 1,
                'indexationFieldsGroups' => ['main'],
            ]);

        $this->websiteReindexRequestDataStorage->expects(self::once())
            ->method('getWebsiteIdsByRelatedJobId')
            ->willReturn([1, 2, 3]);

        $this->jobRunner->expects(self::once())
            ->method('runUniqueByMessage')
            ->willReturn(null);

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($this->message, $this->session)
        );
    }

    public function testProcessWithJobRunnerThrowException(): void
    {
        $exception = new \Exception();

        $this->message->expects(self::once())
            ->method('getBody')
            ->willReturn([
                'relatedJobId' => 1,
                'indexationFieldsGroups' => ['main'],
            ]);

        $this->websiteReindexRequestDataStorage->expects(self::once())
            ->method('getWebsiteIdsByRelatedJobId')
            ->willReturn([1, 2, 3]);

        $this->jobRunner->expects(self::once())
            ->method('runUniqueByMessage')
            ->willThrowException($exception);

        $this->loggerMock->expects(self::once())
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
