<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\Async;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Async\ResizeAllProductImagesProcessor;
use Oro\Bundle\ProductBundle\Async\Topic\ResizeAllProductImagesTopic;
use Oro\Bundle\ProductBundle\Async\Topic\ResizeProductImageChunkTopic;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductImageRepository;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ResizeAllProductImagesProcessorTest extends TestCase
{
    private JobRunner&MockObject $jobRunner;
    private MessageProducerInterface&MockObject $messageProducer;
    private DoctrineHelper&MockObject $doctrineHelper;
    private ProductImageRepository&MockObject $productImageRepository;
    private ResizeAllProductImagesProcessor $processor;

    protected function setUp(): void
    {
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->productImageRepository = $this->createMock(ProductImageRepository::class);

        $this->doctrineHelper
            ->method('getEntityRepository')
            ->with(ProductImage::class)
            ->willReturn($this->productImageRepository);

        $this->processor = new ResizeAllProductImagesProcessor(
            $this->jobRunner,
            $this->messageProducer,
            $this->doctrineHelper
        );
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertSame(
            [ResizeAllProductImagesTopic::getName()],
            ResizeAllProductImagesProcessor::getSubscribedTopics()
        );
    }

    public function testProcessSuccessfully(): void
    {
        $this->processor->setChunkSize(3);
        $force = true;
        $dimensions = ['original', 'large'];

        $message = new Message();
        $message->setMessageId('test-message-id');
        $message->setBody([
            ResizeAllProductImagesTopic::FORCE => $force,
            ResizeAllProductImagesTopic::DIMENSIONS => $dimensions,
        ]);
        $session = $this->createMock(SessionInterface::class);

        $job = $this->createMock(Job::class);
        $job->method('getName')
            ->willReturn('oro_product.image_resize_all');

        // Simulate 5 images, which should result in 2 chunks (3 + 2)
        $this->productImageRepository
            ->expects(self::once())
            ->method('getAllProductImagesIterator')
            ->willReturn(new \ArrayIterator([
                ['id' => 1],
                ['id' => 2],
                ['id' => 3],
                ['id' => 4],
                ['id' => 5],
            ]));

        $this->jobRunner
            ->expects(self::once())
            ->method('runUnique')
            ->with('test-message-id', ResizeAllProductImagesTopic::getName())
            ->willReturnCallback(function ($messageId, $name, $callback) use ($job) {
                return $callback($this->jobRunner, $job);
            });

        $childJob1 = $this->createMock(Job::class);
        $childJob1->method('getId')->willReturn(10);

        $childJob2 = $this->createMock(Job::class);
        $childJob2->method('getId')->willReturn(11);

        $createDelayedCallCount = 0;
        $this->jobRunner
            ->expects(self::exactly(2))
            ->method('createDelayed')
            ->willReturnCallback(function ($name, $callback) use ($childJob1, $childJob2, &$createDelayedCallCount) {
                $createDelayedCallCount++;
                $childJob = $createDelayedCallCount === 1 ? $childJob1 : $childJob2;
                $callback($this->jobRunner, $childJob);
            });

        $sendCallCount = 0;
        $this->messageProducer
            ->expects(self::exactly(2))
            ->method('send')
            ->willReturnCallback(
                function ($topic, $body) use ($force, $dimensions, &$sendCallCount) {
                    $sendCallCount++;
                    self::assertSame(ResizeProductImageChunkTopic::getName(), $topic);

                    if ($sendCallCount === 1) {
                        self::assertSame([
                            ResizeProductImageChunkTopic::JOB_ID => 10,
                            ResizeProductImageChunkTopic::FORCE => $force,
                            ResizeProductImageChunkTopic::IMAGE_IDS => [1, 2, 3],
                            ResizeProductImageChunkTopic::DIMENSIONS => $dimensions,
                        ], $body);
                    } elseif ($sendCallCount === 2) {
                        self::assertSame([
                            ResizeProductImageChunkTopic::JOB_ID => 11,
                            ResizeProductImageChunkTopic::FORCE => $force,
                            ResizeProductImageChunkTopic::IMAGE_IDS => [4, 5],
                            ResizeProductImageChunkTopic::DIMENSIONS => $dimensions,
                        ], $body);
                    }
                }
            );

        $result = $this->processor->process($message, $session);

        self::assertSame(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessWithNoImages(): void
    {
        $message = new Message();
        $message->setMessageId('test-message-id');
        $message->setBody([
            ResizeAllProductImagesTopic::FORCE => false,
            ResizeAllProductImagesTopic::DIMENSIONS => null,
        ]);
        $session = $this->createMock(SessionInterface::class);

        $job = $this->createMock(Job::class);

        $this->productImageRepository
            ->expects(self::once())
            ->method('getAllProductImagesIterator')
            ->willReturn(new \ArrayIterator([]));

        $this->jobRunner
            ->expects(self::once())
            ->method('runUnique')
            ->willReturnCallback(function ($messageId, $name, $callback) use ($job) {
                return $callback($this->jobRunner, $job);
            });

        $this->jobRunner
            ->expects(self::never())
            ->method('createDelayed');

        $this->messageProducer
            ->expects(self::never())
            ->method('send');

        $result = $this->processor->process($message, $session);

        self::assertSame(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessWhenJobRunnerReturnsFalse(): void
    {
        $message = new Message();
        $message->setMessageId('test-message-id');
        $message->setBody([
            ResizeAllProductImagesTopic::FORCE => false,
            ResizeAllProductImagesTopic::DIMENSIONS => null,
        ]);
        $session = $this->createMock(SessionInterface::class);

        $this->jobRunner
            ->expects(self::once())
            ->method('runUnique')
            ->willReturn(false);

        $this->messageProducer
            ->expects(self::never())
            ->method('send');

        $result = $this->processor->process($message, $session);

        self::assertSame(MessageProcessorInterface::REJECT, $result);
    }

    public function testProcessWithExactChunkSize(): void
    {
        $this->processor->setChunkSize(2);

        $message = new Message();
        $message->setMessageId('test-message-id');
        $message->setBody([
            ResizeAllProductImagesTopic::FORCE => false,
            ResizeAllProductImagesTopic::DIMENSIONS => null,
        ]);
        $session = $this->createMock(SessionInterface::class);

        $job = $this->createMock(Job::class);
        $job->method('getName')->willReturn('oro_product.image_resize_all');

        // Exactly 4 images = 2 chunks of 2
        $this->productImageRepository
            ->expects(self::once())
            ->method('getAllProductImagesIterator')
            ->willReturn(new \ArrayIterator([
                ['id' => 1],
                ['id' => 2],
                ['id' => 3],
                ['id' => 4],
            ]));

        $this->jobRunner
            ->expects(self::once())
            ->method('runUnique')
            ->willReturnCallback(function ($messageId, $name, $callback) use ($job) {
                return $callback($this->jobRunner, $job);
            });

        $childJob1 = $this->createMock(Job::class);
        $childJob1->method('getId')->willReturn(10);

        $childJob2 = $this->createMock(Job::class);
        $childJob2->method('getId')->willReturn(11);

        $createDelayedCallCount = 0;
        $this->jobRunner
            ->expects(self::exactly(2))
            ->method('createDelayed')
            ->willReturnCallback(function ($name, $callback) use ($childJob1, $childJob2, &$createDelayedCallCount) {
                $createDelayedCallCount++;
                $childJob = $createDelayedCallCount === 1 ? $childJob1 : $childJob2;
                $callback($this->jobRunner, $childJob);
            });

        $this->messageProducer
            ->expects(self::exactly(2))
            ->method('send');

        $result = $this->processor->process($message, $session);

        self::assertSame(MessageProcessorInterface::ACK, $result);
    }
}
