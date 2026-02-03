<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\Async;

use Oro\Bundle\ProductBundle\Async\ResizeProductImageChunkProcessor;
use Oro\Bundle\ProductBundle\Async\Topic\ResizeProductImageChunkTopic;
use Oro\Bundle\ProductBundle\Async\Topic\ResizeProductImageTopic;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ResizeProductImageChunkProcessorTest extends TestCase
{
    private JobRunner&MockObject $jobRunner;
    private MessageProducerInterface&MockObject $messageProducer;
    private ResizeProductImageChunkProcessor $processor;

    protected function setUp(): void
    {
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);

        $this->processor = new ResizeProductImageChunkProcessor(
            $this->jobRunner,
            $this->messageProducer
        );
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertSame(
            [ResizeProductImageChunkTopic::getName()],
            ResizeProductImageChunkProcessor::getSubscribedTopics()
        );
    }

    public function testProcessSuccessfully(): void
    {
        $jobId = 123;
        $imageIds = [1, 2, 3];
        $force = true;
        $dimensions = ['original', 'large'];

        $message = new Message();
        $message->setBody([
            ResizeProductImageChunkTopic::JOB_ID => $jobId,
            ResizeProductImageChunkTopic::IMAGE_IDS => $imageIds,
            ResizeProductImageChunkTopic::FORCE => $force,
            ResizeProductImageChunkTopic::DIMENSIONS => $dimensions,
        ]);
        $session = $this->createMock(SessionInterface::class);

        $this->jobRunner
            ->expects(self::once())
            ->method('runDelayed')
            ->with($jobId)
            ->willReturnCallback(function ($jobId, $callback) {
                return $callback();
            });

        $sendCallCount = 0;
        $this->messageProducer
            ->expects(self::exactly(3))
            ->method('send')
            ->willReturnCallback(function ($topic, $body) use ($force, $dimensions, $imageIds, &$sendCallCount) {
                $sendCallCount++;
                self::assertSame(ResizeProductImageTopic::getName(), $topic);
                self::assertSame([
                    ResizeProductImageTopic::FORCE_OPTION => $force,
                    ResizeProductImageTopic::PRODUCT_IMAGE_ID_OPTION => $imageIds[$sendCallCount - 1],
                    ResizeProductImageTopic::DIMENSIONS_OPTION => $dimensions,
                ], $body);
            });

        $result = $this->processor->process($message, $session);

        self::assertSame(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessWithDefaultValues(): void
    {
        $jobId = 456;
        $imageIds = [10, 20];
        $force = false;
        $dimensions = null;

        $message = new Message();
        $message->setBody([
            ResizeProductImageChunkTopic::JOB_ID => $jobId,
            ResizeProductImageChunkTopic::IMAGE_IDS => $imageIds,
            ResizeProductImageChunkTopic::FORCE => $force,
            ResizeProductImageChunkTopic::DIMENSIONS => $dimensions,
        ]);
        $session = $this->createMock(SessionInterface::class);

        $this->jobRunner
            ->expects(self::once())
            ->method('runDelayed')
            ->with($jobId)
            ->willReturnCallback(function ($jobId, $callback) {
                return $callback();
            });

        $sendCallCount = 0;
        $this->messageProducer
            ->expects(self::exactly(2))
            ->method('send')
            ->willReturnCallback(function ($topic, $body) use ($imageIds, &$sendCallCount) {
                $sendCallCount++;
                self::assertSame(ResizeProductImageTopic::getName(), $topic);
                self::assertSame([
                    ResizeProductImageTopic::FORCE_OPTION => false,
                    ResizeProductImageTopic::PRODUCT_IMAGE_ID_OPTION => $imageIds[$sendCallCount - 1],
                    ResizeProductImageTopic::DIMENSIONS_OPTION => null,
                ], $body);
            });

        $result = $this->processor->process($message, $session);

        self::assertSame(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessWhenJobRunnerReturnsFalse(): void
    {
        $jobId = 789;
        $imageIds = [1, 2, 3];

        $message = new Message();
        $message->setBody([
            ResizeProductImageChunkTopic::JOB_ID => $jobId,
            ResizeProductImageChunkTopic::IMAGE_IDS => $imageIds,
            ResizeProductImageChunkTopic::FORCE => false,
            ResizeProductImageChunkTopic::DIMENSIONS => null,
        ]);
        $session = $this->createMock(SessionInterface::class);

        $this->jobRunner
            ->expects(self::once())
            ->method('runDelayed')
            ->with($jobId)
            ->willReturn(false);

        $this->messageProducer
            ->expects(self::never())
            ->method('send');

        $result = $this->processor->process($message, $session);

        self::assertSame(MessageProcessorInterface::REJECT, $result);
    }

    public function testProcessWithEmptyImageIds(): void
    {
        $jobId = 100;
        $imageIds = [];

        $message = new Message();
        $message->setBody([
            ResizeProductImageChunkTopic::JOB_ID => $jobId,
            ResizeProductImageChunkTopic::IMAGE_IDS => $imageIds,
            ResizeProductImageChunkTopic::FORCE => false,
            ResizeProductImageChunkTopic::DIMENSIONS => null,
        ]);
        $session = $this->createMock(SessionInterface::class);

        $this->jobRunner
            ->expects(self::once())
            ->method('runDelayed')
            ->with($jobId)
            ->willReturnCallback(function ($jobId, $callback) {
                return $callback();
            });

        $this->messageProducer
            ->expects(self::never())
            ->method('send');

        $result = $this->processor->process($message, $session);

        self::assertSame(MessageProcessorInterface::ACK, $result);
    }
}
