<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\Async;

use Oro\Bundle\ProductBundle\Async\ProductFallbackProcessChunkProcessor;
use Oro\Bundle\ProductBundle\Async\Topic\ProductFallbackProcessChunkTopic;
use Oro\Bundle\ProductBundle\Manager\ProductFallbackUpdateManager;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class ProductFallbackProcessChunkProcessorTest extends TestCase
{
    private JobRunner&MockObject $jobRunner;
    private ProductFallbackUpdateManager&MockObject $updateManager;
    private LoggerInterface&MockObject $logger;
    private ProductFallbackProcessChunkProcessor $processor;

    protected function setUp(): void
    {
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->updateManager = $this->createMock(ProductFallbackUpdateManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new ProductFallbackProcessChunkProcessor(
            $this->jobRunner,
            $this->updateManager
        );
        $this->processor->setLogger($this->logger);
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertSame(
            [ProductFallbackProcessChunkTopic::getName()],
            ProductFallbackProcessChunkProcessor::getSubscribedTopics()
        );
    }

    public function testProcessSuccessfully(): void
    {
        $jobId = 123;
        $productIds = [1, 2, 3, 4, 5];

        $message = new Message();
        $message->setBody([
            ProductFallbackProcessChunkTopic::JOB_ID => $jobId,
            ProductFallbackProcessChunkTopic::PRODUCT_IDS => $productIds,
        ]);
        $session = $this->createMock(SessionInterface::class);

        $this->jobRunner
            ->expects(self::once())
            ->method('runDelayed')
            ->with($jobId)
            ->willReturnCallback(function ($jobId, $callback) {
                return $callback();
            });

        $this->updateManager
            ->expects(self::once())
            ->method('processChunk')
            ->with($productIds);

        $this->logger
            ->expects(self::never())
            ->method('error');

        $result = $this->processor->process($message, $session);

        self::assertSame(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessWhenJobRunnerReturnsFalse(): void
    {
        $jobId = 123;
        $productIds = [1, 2, 3];

        $message = new Message();
        $message->setBody([
            ProductFallbackProcessChunkTopic::JOB_ID => $jobId,
            ProductFallbackProcessChunkTopic::PRODUCT_IDS => $productIds,
        ]);
        $session = $this->createMock(SessionInterface::class);

        $this->jobRunner
            ->expects(self::once())
            ->method('runDelayed')
            ->with($jobId)
            ->willReturn(false);

        $this->updateManager
            ->expects(self::never())
            ->method('processChunk');

        $result = $this->processor->process($message, $session);

        self::assertSame(MessageProcessorInterface::REJECT, $result);
    }

    public function testProcessWithException(): void
    {
        $jobId = 123;
        $productIds = [1, 2, 3, 4, 5];
        $exception = new \RuntimeException('Test exception');

        $message = new Message();
        $message->setBody([
            ProductFallbackProcessChunkTopic::JOB_ID => $jobId,
            ProductFallbackProcessChunkTopic::PRODUCT_IDS => $productIds,
        ]);
        $session = $this->createMock(SessionInterface::class);

        $this->jobRunner
            ->expects(self::once())
            ->method('runDelayed')
            ->with($jobId)
            ->willThrowException($exception);

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with(
                self::stringContains('Failed to process product fallback chunk'),
                [
                    'exception' => $exception,
                    'productIds' => $productIds,
                ]
            );

        $result = $this->processor->process($message, $session);

        self::assertSame(MessageProcessorInterface::REJECT, $result);
    }

    public function testProcessWithExceptionInUpdateManager(): void
    {
        $jobId = 123;
        $productIds = [10, 20, 30];
        $exception = new \Exception('Update manager failed');

        $message = new Message();
        $message->setBody([
            ProductFallbackProcessChunkTopic::JOB_ID => $jobId,
            ProductFallbackProcessChunkTopic::PRODUCT_IDS => $productIds,
        ]);
        $session = $this->createMock(SessionInterface::class);

        $this->jobRunner
            ->expects(self::once())
            ->method('runDelayed')
            ->with($jobId)
            ->willReturnCallback(function ($jobId, $callback) use ($exception) {
                $callback();
            });

        $this->updateManager
            ->expects(self::once())
            ->method('processChunk')
            ->with($productIds)
            ->willThrowException($exception);

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with(
                self::stringContains('Failed to process product fallback chunk'),
                [
                    'exception' => $exception,
                    'productIds' => $productIds,
                ]
            );

        $result = $this->processor->process($message, $session);

        self::assertSame(MessageProcessorInterface::REJECT, $result);
    }
}
