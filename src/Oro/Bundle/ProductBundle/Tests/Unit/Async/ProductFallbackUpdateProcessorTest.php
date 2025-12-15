<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\Async;

use Oro\Bundle\ProductBundle\Async\ProductFallbackUpdateProcessor;
use Oro\Bundle\ProductBundle\Async\Topic\ProductFallbackFinalizeTopic;
use Oro\Bundle\ProductBundle\Async\Topic\ProductFallbackProcessChunkTopic;
use Oro\Bundle\ProductBundle\Async\Topic\ProductFallbackUpdateTopic;
use Oro\Bundle\ProductBundle\Manager\ProductFallbackUpdateManager;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\DependentJobContext;
use Oro\Component\MessageQueue\Job\DependentJobService;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ProductFallbackUpdateProcessorTest extends TestCase
{
    private JobRunner&MockObject $jobRunner;
    private MessageProducerInterface&MockObject $producer;
    private ProductFallbackUpdateManager&MockObject $updateManager;
    private DependentJobService&MockObject $dependentJobService;
    private LoggerInterface&MockObject $logger;
    private ProductFallbackUpdateProcessor $processor;

    protected function setUp(): void
    {
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->updateManager = $this->createMock(ProductFallbackUpdateManager::class);
        $this->dependentJobService = $this->createMock(DependentJobService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new ProductFallbackUpdateProcessor(
            $this->jobRunner,
            $this->producer,
            $this->updateManager,
            $this->dependentJobService
        );
        $this->processor->setLogger($this->logger);
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertSame(
            [ProductFallbackUpdateTopic::getName()],
            ProductFallbackUpdateProcessor::getSubscribedTopics()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessSuccessfully(): void
    {
        $batchSize = 100;
        $message = new Message();
        $message->setBody([
            ProductFallbackUpdateTopic::BATCH_SIZE_OPTION => $batchSize,
        ]);
        $session = $this->createMock(SessionInterface::class);

        $rootJob = $this->createMock(Job::class);
        $rootJob->expects(self::any())
            ->method('getId')
            ->willReturn(1);

        $job = $this->createMock(Job::class);
        $job->expects(self::any())
            ->method('getRootJob')
            ->willReturn($rootJob);
        $job->expects(self::any())
            ->method('getName')
            ->willReturn('oro:product:fallback:update');

        $this->logger
            ->expects(self::once())
            ->method('notice')
            ->with('Started fixing product entity field fallback values');

        $this->updateManager
            ->expects(self::once())
            ->method('getProductIdChunks')
            ->with($batchSize)
            ->willReturn(new \ArrayIterator([
                [1, 2, 3],
                [4, 5, 6],
            ]));

        $childJob1 = $this->createMock(Job::class);
        $childJob1->expects(self::once())
            ->method('getId')
            ->willReturn(10);

        $childJob2 = $this->createMock(Job::class);
        $childJob2->expects(self::once())
            ->method('getId')
            ->willReturn(11);

        $this->jobRunner
            ->expects(self::once())
            ->method('runUniqueByMessage')
            ->with($message)
            ->willReturnCallback(function ($message, $callback) use ($job) {
                return $callback($this->jobRunner, $job);
            });

        $callCount = 0;
        $this->jobRunner
            ->expects(self::exactly(2))
            ->method('createDelayed')
            ->willReturnCallback(function ($name, $callback) use ($childJob1, $childJob2, &$callCount) {
                $callCount++;
                $childJob = $callCount === 1 ? $childJob1 : $childJob2;
                return $callback($this->jobRunner, $childJob);
            });

        $sendCallCount = 0;
        $this->producer
            ->expects(self::exactly(2))
            ->method('send')
            ->willReturnCallback(function ($topic, $body) use (&$sendCallCount) {
                $sendCallCount++;

                self::assertSame(ProductFallbackProcessChunkTopic::getName(), $topic);

                if ($sendCallCount === 1) {
                    self::assertSame([
                        ProductFallbackProcessChunkTopic::JOB_ID => 10,
                        ProductFallbackProcessChunkTopic::PRODUCT_IDS => [1, 2, 3],
                    ], $body);
                } elseif ($sendCallCount === 2) {
                    self::assertSame([
                        ProductFallbackProcessChunkTopic::JOB_ID => 11,
                        ProductFallbackProcessChunkTopic::PRODUCT_IDS => [4, 5, 6],
                    ], $body);
                }
            });

        $context = $this->createMock(DependentJobContext::class);
        $context->expects(self::once())
            ->method('addDependentJob')
            ->with(
                ProductFallbackFinalizeTopic::getName(),
                [ProductFallbackFinalizeTopic::JOB_ID => 1]
            );

        $this->dependentJobService
            ->expects(self::once())
            ->method('createDependentJobContext')
            ->with($rootJob)
            ->willReturn($context);

        $this->dependentJobService
            ->expects(self::once())
            ->method('saveDependentJob')
            ->with($context);

        $result = $this->processor->process($message, $session);

        self::assertSame(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessWithNoChunks(): void
    {
        $batchSize = 100;
        $message = new Message();
        $message->setBody([
            ProductFallbackUpdateTopic::BATCH_SIZE_OPTION => $batchSize,
        ]);
        $session = $this->createMock(SessionInterface::class);

        $rootJob = $this->createMock(Job::class);
        $rootJob->expects(self::any())
            ->method('getId')
            ->willReturn(1);

        $job = $this->createMock(Job::class);
        $job->expects(self::any())
            ->method('getRootJob')
            ->willReturn($rootJob);

        $this->logger
            ->expects(self::once())
            ->method('notice')
            ->with('Started fixing product entity field fallback values');

        $this->logger
            ->expects(self::once())
            ->method('info')
            ->with('No product fallback updates were required.');

        $this->updateManager
            ->expects(self::once())
            ->method('getProductIdChunks')
            ->with($batchSize)
            ->willReturn(new \ArrayIterator([]));

        $this->jobRunner
            ->expects(self::once())
            ->method('runUniqueByMessage')
            ->with($message)
            ->willReturnCallback(function ($message, $callback) use ($job) {
                return $callback($this->jobRunner, $job);
            });

        $this->jobRunner
            ->expects(self::never())
            ->method('createDelayed');

        $this->producer
            ->expects(self::never())
            ->method('send');

        $context = $this->createMock(DependentJobContext::class);

        $this->dependentJobService
            ->expects(self::once())
            ->method('createDependentJobContext')
            ->with($rootJob)
            ->willReturn($context);

        $result = $this->processor->process($message, $session);

        self::assertSame(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessWithException(): void
    {
        $batchSize = 100;
        $message = new Message();
        $message->setBody([
            ProductFallbackUpdateTopic::BATCH_SIZE_OPTION => $batchSize,
        ]);
        $session = $this->createMock(SessionInterface::class);
        $exception = new \RuntimeException('Test exception');

        $this->logger
            ->expects(self::once())
            ->method('notice')
            ->with('Started fixing product entity field fallback values');

        $this->jobRunner
            ->expects(self::once())
            ->method('runUniqueByMessage')
            ->with($message)
            ->willThrowException($exception);

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with(
                self::stringContains('Failed to schedule product fallback update jobs'),
                [
                    'exception' => $exception,
                    'batchSize' => $batchSize,
                ]
            );

        $result = $this->processor->process($message, $session);

        self::assertSame(MessageProcessorInterface::REJECT, $result);
    }

    public function testProcessWhenJobRunnerReturnsFalse(): void
    {
        $batchSize = 100;
        $message = new Message();
        $message->setBody([
            ProductFallbackUpdateTopic::BATCH_SIZE_OPTION => $batchSize,
        ]);
        $session = $this->createMock(SessionInterface::class);

        $this->logger
            ->expects(self::once())
            ->method('notice')
            ->with('Started fixing product entity field fallback values');

        $this->jobRunner
            ->expects(self::once())
            ->method('runUniqueByMessage')
            ->with($message)
            ->willReturn(false);

        $result = $this->processor->process($message, $session);

        self::assertSame(MessageProcessorInterface::REJECT, $result);
    }
}
