<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Async;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Async\ReindexProductsByAttributesProcessor;
use Oro\Bundle\ProductBundle\Async\Topic\ReindexProductsByAttributesTopic;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Test\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ReindexProductsByAttributesProcessorTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private JobRunner|\PHPUnit\Framework\MockObject\MockObject $jobRunner;

    private ProductRepository|\PHPUnit\Framework\MockObject\MockObject $repository;

    private EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $dispatcher;

    private ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $registry;

    private SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session;

    private ReindexProductsByAttributesProcessor $processor;

    protected function setUp(): void
    {
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->session = $this->createMock(SessionInterface::class);
        $this->repository = $this->createMock(ProductRepository::class);

        $this->processor = new ReindexProductsByAttributesProcessor(
            $this->jobRunner,
            $this->registry,
            $this->dispatcher
        );

        $this->setUpLoggerMock($this->processor);
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals(
            [ReindexProductsByAttributesTopic::getName()],
            ReindexProductsByAttributesProcessor::getSubscribedTopics()
        );
    }

    public function testProcessWhenUnexpectedExceptionOccurred(): void
    {
        $messageBody = ['attributeIds' => [1, 2]];
        $message = $this->getMessage($messageBody);

        $this->jobRunner->expects(self::once())
            ->method('runUniqueByMessage')
            ->willThrowException(new \Exception());

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with('Unexpected exception occurred during queue message processing');

        $result = $this->processor->process($message, $this->session);
        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    /**
     * @param array $productIds
     * @param \PHPUnit\Framework\MockObject\Rule\InvokedCount $dispatchExpected
     *
     * @dataProvider getProductIds
     */
    public function testProcess($productIds, $dispatchExpected): void
    {
        $attributeIds = [1, 2];
        $messageBody = ['attributeIds' => $attributeIds];
        $message = $this->getMessage($messageBody);

        $this->mockRunUniqueJob($message);

        $this->repository->expects(self::once())
            ->method('getProductIdsByAttributesId')
            ->with($attributeIds)
            ->willReturn($productIds);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects(self::any())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($this->repository);
        $this->registry->expects(self::any())
            ->method('getManagerForClass')
            ->with(Product::class)
            ->willReturn($manager);

        $this->dispatcher->expects($dispatchExpected)
            ->method('dispatch')
            ->with(
                new ReindexationRequestEvent([Product::class], [], $productIds, true, ['main']),
                ReindexationRequestEvent::EVENT_NAME
            );

        $result = $this->processor->process($message, $this->session);
        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessWithExceptionDuringReindexEventDispatching(): void
    {
        $attributeIds = [1, 2];
        $messageBody = ['attributeIds' => $attributeIds];
        $message = $this->getMessage($messageBody);

        $this->mockRunUniqueJob($message);

        $this->repository->expects(self::once())
            ->method('getProductIdsByAttributesId')
            ->with($attributeIds)
            ->willReturn([1]);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects(self::any())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($this->repository);
        $this->registry->expects(self::any())
            ->method('getManagerForClass')
            ->with(Product::class)
            ->willReturn($manager);

        $exception = new \Exception();
        $this->dispatcher->expects(self::once())
            ->method('dispatch')
            ->willThrowException($exception);

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during triggering update of search index ',
                [
                    'exception' => $exception,
                    'topic' => ReindexProductsByAttributesTopic::getName()
                ]
            );

        $result = $this->processor->process($message, $this->session);
        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function getProductIds(): array
    {
        return [
            'empty array' => [
                'productIds' => [],
                'dispatchExpected' => self::never()
            ],
            'array with id' => [
                'productIds' => [100, 101, 102],
                'dispatchExpected' => self::once()
            ]
        ];
    }

    private function getMessage(array $body = []): MessageInterface
    {
        $message = new Message();
        $message->setBody($body);
        $message->setMessageId('msg-1');

        return $message;
    }

    private function mockRunUniqueJob(MessageInterface $expectedMessage): void
    {
        $job = new Job();
        $job->setId(1);

        $childJob = new Job();
        $childJob->setId(2);
        $childJob->setRootJob($job);
        $childJob->setName(ReindexProductsByAttributesTopic::getName());

        $this->jobRunner->expects(self::once())
            ->method('runUniqueByMessage')
            ->with($expectedMessage)
            ->willReturnCallback(function ($actualMessage, $callback) use ($expectedMessage, $childJob) {
                self::assertEquals($actualMessage, $expectedMessage);

                return $callback($this->jobRunner, $childJob);
            });
    }
}
