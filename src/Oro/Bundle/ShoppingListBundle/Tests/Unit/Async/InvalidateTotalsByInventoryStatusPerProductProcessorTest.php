<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Async;

use Doctrine\DBAL\Driver\DriverException;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ShoppingListBundle\Async\InvalidateTotalsByInventoryStatusPerProductProcessor;
use Oro\Bundle\ShoppingListBundle\Async\MessageFactory;
use Oro\Bundle\ShoppingListBundle\Async\Topics;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListTotalRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;

class InvalidateTotalsByInventoryStatusPerProductProcessorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /**
     * @var MessageFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $messageFactory;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var InvalidateTotalsByInventoryStatusPerProductProcessor
     */
    private $processor;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->messageFactory = $this->createMock(MessageFactory::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new InvalidateTotalsByInventoryStatusPerProductProcessor(
            $this->registry,
            $this->messageFactory,
            $this->logger
        );
    }

    public function testGetSubscribedTopics()
    {
        $this->assertEquals(
            [Topics::INVALIDATE_TOTALS_BY_INVENTORY_STATUS_PER_PRODUCT],
            InvalidateTotalsByInventoryStatusPerProductProcessor::getSubscribedTopics()
        );
    }

    public function testProcessWithoutContext()
    {
        /** @var SessionInterface $session */
        $session = $this->createMock(SessionInterface::class);
        $data = [];
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(JSON::encode($data));

        $this->messageFactory->expects($this->once())
            ->method('getContext')
            ->with($data)
            ->willReturn(null);

        $this->assertEquals(
            InvalidateTotalsByInventoryStatusPerProductProcessor::ACK,
            $this->processor->process($message, $session)
        );
    }

    public function testProcess()
    {
        $context = $this->getEntity(Website::class, ['id' => 42]);
        $productIds = [1, 2];
        /** @var SessionInterface $session */
        $session = $this->createMock(SessionInterface::class);
        $data = [
            'products' => $productIds,
            'context' => [
                'class' => Website::class,
                'id' => 42
            ]
        ];
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(JSON::encode($data));

        $this->messageFactory->expects($this->once())
            ->method('getContext')
            ->with($data)
            ->willReturn($context);
        $this->messageFactory->expects($this->once())
            ->method('getProductIds')
            ->with($data)
            ->willReturn($productIds);

        $repo = $this->createMock(ShoppingListTotalRepository::class);
        $repo->expects($this->once())
            ->method('invalidateByProducts')
            ->with($context, $productIds);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(ShoppingListTotal::class)
            ->willReturn($repo);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ShoppingListTotal::class)
            ->willReturn($em);

        $this->assertEquals(
            InvalidateTotalsByInventoryStatusPerProductProcessor::ACK,
            $this->processor->process($message, $session)
        );
    }

    public function testProcessRetryableException()
    {
        $context = $this->getEntity(Website::class, ['id' => 4]);
        $productIds = [1, 3];
        /** @var SessionInterface $session */
        $session = $this->createMock(SessionInterface::class);
        $data = [
            'products' => $productIds,
            'context' => [
                'class' => Website::class,
                'id' => 4
            ]
        ];
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(JSON::encode($data));

        $this->messageFactory->expects($this->once())
            ->method('getContext')
            ->with($data)
            ->willReturn($context);
        $this->messageFactory->expects($this->once())
            ->method('getProductIds')
            ->with($data)
            ->willReturn($productIds);

        /** @var DriverException $driverException */
        $driverException = $this->createMock(DriverException::class);
        $e = new DeadlockException('deadlock detected', $driverException);

        $repo = $this->createMock(ShoppingListTotalRepository::class);
        $repo->expects($this->once())
            ->method('invalidateByProducts')
            ->with($context, $productIds)
            ->willThrowException($e);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(ShoppingListTotal::class)
            ->willReturn($repo);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(ShoppingListTotal::class)
            ->willReturn($em);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Retryable database exception occurred during shopping list totals invalidation',
                ['exception' => $e]
            );

        $this->assertEquals(
            InvalidateTotalsByInventoryStatusPerProductProcessor::REQUEUE,
            $this->processor->process($message, $session)
        );
    }
}
