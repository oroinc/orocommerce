<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Async;

use Doctrine\DBAL\Driver\DriverException;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ShoppingListBundle\Async\InvalidateTotalsByInventoryStatusPerProductProcessor;
use Oro\Bundle\ShoppingListBundle\Async\MessageFactory;
use Oro\Bundle\ShoppingListBundle\Async\Topic\InvalidateTotalsByInventoryStatusPerProductTopic;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListTotalRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class InvalidateTotalsByInventoryStatusPerProductProcessorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;
    use LoggerAwareTraitTestTrait;

    private ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $registry;

    private MessageFactory|\PHPUnit\Framework\MockObject\MockObject $messageFactory;

    private InvalidateTotalsByInventoryStatusPerProductProcessor $processor;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->messageFactory = $this->createMock(MessageFactory::class);

        $this->processor = new InvalidateTotalsByInventoryStatusPerProductProcessor(
            $this->registry,
            $this->messageFactory,
        );

        $this->setUpLoggerMock($this->processor);
        $this->testSetLogger();
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals(
            [InvalidateTotalsByInventoryStatusPerProductTopic::getName()],
            InvalidateTotalsByInventoryStatusPerProductProcessor::getSubscribedTopics()
        );
    }

    public function testProcessWithoutContext(): void
    {
        /** @var SessionInterface $session */
        $session = $this->createMock(SessionInterface::class);
        $data = [];
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::any())
            ->method('getBody')
            ->willReturn($data);

        $this->messageFactory->expects(self::once())
            ->method('getContext')
            ->with($data)
            ->willReturn(null);

        self::assertEquals(
            InvalidateTotalsByInventoryStatusPerProductProcessor::ACK,
            $this->processor->process($message, $session)
        );
    }

    public function testProcess(): void
    {
        $context = $this->getEntity(Website::class, ['id' => 42]);
        $productIds = [1, 2];
        /** @var SessionInterface $session */
        $session = $this->createMock(SessionInterface::class);
        $data = [
            'products' => $productIds,
            'context' => [
                'class' => Website::class,
                'id' => 42,
            ],
        ];
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::any())
            ->method('getBody')
            ->willReturn($data);

        $this->messageFactory->expects(self::once())
            ->method('getContext')
            ->with($data)
            ->willReturn($context);
        $this->messageFactory->expects(self::once())
            ->method('getProductIds')
            ->with($data)
            ->willReturn($productIds);

        $repo = $this->createMock(ShoppingListTotalRepository::class);
        $repo->expects(self::once())
            ->method('invalidateByProducts')
            ->with($context, $productIds);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('getRepository')
            ->with(ShoppingListTotal::class)
            ->willReturn($repo);
        $this->registry->expects(self::once())
            ->method('getManagerForClass')
            ->with(ShoppingListTotal::class)
            ->willReturn($em);

        self::assertEquals(
            InvalidateTotalsByInventoryStatusPerProductProcessor::ACK,
            $this->processor->process($message, $session)
        );
    }

    public function testProcessRetryableException(): void
    {
        $context = $this->getEntity(Website::class, ['id' => 4]);
        $productIds = [1, 3];
        /** @var SessionInterface $session */
        $session = $this->createMock(SessionInterface::class);
        $data = [
            'products' => $productIds,
            'context' => [
                'class' => Website::class,
                'id' => 4,
            ],
        ];
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::any())
            ->method('getBody')
            ->willReturn($data);

        $this->messageFactory->expects(self::once())
            ->method('getContext')
            ->with($data)
            ->willReturn($context);
        $this->messageFactory->expects(self::once())
            ->method('getProductIds')
            ->with($data)
            ->willReturn($productIds);

        /** @var DriverException $driverException */
        $driverException = $this->createMock(DriverException::class);
        $e = new DeadlockException('deadlock detected', $driverException);

        $repo = $this->createMock(ShoppingListTotalRepository::class);
        $repo->expects(self::once())
            ->method('invalidateByProducts')
            ->with($context, $productIds)
            ->willThrowException($e);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('getRepository')
            ->with(ShoppingListTotal::class)
            ->willReturn($repo);
        $this->registry->expects(self::once())
            ->method('getManagerForClass')
            ->with(ShoppingListTotal::class)
            ->willReturn($em);

        $this->assertLoggerErrorMethodCalled();

        self::assertEquals(
            InvalidateTotalsByInventoryStatusPerProductProcessor::REQUEUE,
            $this->processor->process($message, $session)
        );
    }
}
