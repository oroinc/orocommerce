<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Async;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;
use Oro\Bundle\OrderBundle\Async\PrecalculateOrderTotalProcessor;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM\Stub\OrderStub;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PrecalculateOrderTotalProcessorTest extends TestCase
{
    private MessageProducerInterface&MockObject $producer;
    private ManagerRegistry&MockObject $doctrine;
    private TotalHelper&MockObject $totalHelper;
    private OptionalListenerManager&MockObject $optionalListenerManager;
    private PrecalculateOrderTotalProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->totalHelper = $this->createMock(TotalHelper::class);
        $this->optionalListenerManager = $this->createMock(OptionalListenerManager::class);

        $this->processor = new PrecalculateOrderTotalProcessor(
            $this->producer,
            $this->doctrine,
            $this->totalHelper,
            $this->optionalListenerManager
        );
    }

    private static function getOrderIdRows(int $firstOrderId, int $lastOrderId): array
    {
        $rows = [];
        for ($i = $lastOrderId; $i >= $firstOrderId; $i--) {
            $rows[] = ['id' => $i];
        }

        return $rows;
    }

    private static function getOrder(int $id, mixed $total, mixed $precalculatedTotal): Order
    {
        $order = new OrderStub();
        ReflectionUtil::setId($order, $id);
        $order->setTotalObject(self::getOrderTotal($total));
        if (null !== $precalculatedTotal) {
            $order->setSerializedData(['precalculatedTotal' => $precalculatedTotal]);
        }

        return $order;
    }

    private static function getOrderTotal(mixed $value): MultiCurrency
    {
        $result = new MultiCurrency();
        $result->setValue($value);

        return $result;
    }

    /**
     * @dataProvider splitOnBatchesDataProvider
     */
    public function testSplitOnBatches(array $orderIdRows, array $sendMessages): void
    {
        $lastOrderId = 301;

        $taxEntityManager = $this->createMock(EntityManagerInterface::class);
        $orderEntityManager = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::exactly(2))
            ->method('getManagerForClass')
            ->willReturnMap([
                [Tax::class, $taxEntityManager],
                [Order::class, $orderEntityManager]
            ]);

        $taxQb = $this->createMock(QueryBuilder::class);
        $taxQuery = $this->createMock(AbstractQuery::class);
        $taxEntityManager->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($taxQb);
        $taxQb->expects(self::once())
            ->method('from')
            ->with(Tax::class, 't')
            ->willReturnSelf();
        $taxQb->expects(self::once())
            ->method('select')
            ->with('COUNT(t)')
            ->willReturnSelf();
        $taxQb->expects(self::once())
            ->method('getQuery')
            ->willReturn($taxQuery);
        $taxQuery->expects(self::once())
            ->method('getSingleScalarResult')
            ->willReturn(1);

        $orderQb = $this->createMock(QueryBuilder::class);
        $orderQuery = $this->createMock(AbstractQuery::class);
        $orderEntityManager->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($orderQb);
        $orderQb->expects(self::once())
            ->method('from')
            ->with(Order::class, 'o')
            ->willReturnSelf();
        $orderQb->expects(self::once())
            ->method('select')
            ->with('o.id')
            ->willReturnSelf();
        $orderQb->expects(self::once())
            ->method('where')
            ->with('o.id <= :lastOrderId')
            ->willReturnSelf();
        $orderQb->expects(self::once())
            ->method('setParameter')
            ->with('lastOrderId', $lastOrderId)
            ->willReturnSelf();
        $orderQb->expects(self::once())
            ->method('orderBy')
            ->with('o.id', 'DESC')
            ->willReturnSelf();
        $orderQb->expects(self::once())
            ->method('getQuery')
            ->willReturn($orderQuery);
        $orderQuery->expects(self::once())
            ->method('getArrayResult')
            ->willReturn($orderIdRows);

        $sendItems = [];
        foreach ($sendMessages as $sendMessage) {
            $sendItems[] = ['oro.order.precalculate_order_total', $sendMessage];
        }
        $this->producer->expects(self::exactly(\count($sendItems)))
            ->method('send')
            ->withConsecutive(...$sendItems);

        $message = new Message();
        $message->setBody(['lastOrderId' => $lastOrderId]);

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public static function splitOnBatchesDataProvider(): array
    {
        return [
            [[], []],
            [
                self::getOrderIdRows(1, 1),
                [
                    ['firstOrderId' => 1, 'lastOrderId' => 1]
                ]
            ],
            [
                self::getOrderIdRows(2, 301),
                [
                    ['firstOrderId' => 2, 'lastOrderId' => 301]
                ]
            ],
            [
                self::getOrderIdRows(1, 301),
                [
                    ['firstOrderId' => 2, 'lastOrderId' => 301],
                    ['firstOrderId' => 1, 'lastOrderId' => 1]
                ]
            ],
            [
                self::getOrderIdRows(1, 601),
                [
                    ['firstOrderId' => 302, 'lastOrderId' => 601],
                    ['firstOrderId' => 2, 'lastOrderId' => 301],
                    ['firstOrderId' => 1, 'lastOrderId' => 1]
                ]
            ]
        ];
    }

    public function testBatchProcessing(): void
    {
        $order1 = self::getOrder(1, 1.1, null);
        $order2 = self::getOrder(2, 2.1, 2.2);
        $order3 = self::getOrder(3, 3.1, null);
        $order3->setSerializedData(['another' => 1]);
        $order4 = self::getOrder(1, 4.1, null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Order::class)
            ->willReturn($entityManager);

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $entityManager->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($qb);
        $qb->expects(self::once())
            ->method('from')
            ->with(Order::class, 'o')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('select')
            ->with('o')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('where')
            ->with('o.id >= :firstOrderId AND o.id <= :lastOrderId')
            ->willReturnSelf();
        $qb->expects(self::exactly(2))
            ->method('setParameter')
            ->withConsecutive(['firstOrderId', 1], ['lastOrderId', 3])
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getResult')
            ->willReturn([$order1, $order2, $order3]);

        $this->totalHelper->expects(self::exactly(2))
            ->method('calculateTotal')
            ->willReturnMap([
                [$order1, self::getOrderTotal(1.2)],
                [$order3, self::getOrderTotal(3.2)]
            ]);
        $entityManager->expects(self::once())
            ->method('flush')
            ->willReturnCallback(function () use ($order1, $order2, $order3, $order4) {
                self::assertSame(['precalculatedTotal' => 1.2], $order1->getSerializedData(), 'ord1');
                self::assertSame(['precalculatedTotal' => 2.2], $order2->getSerializedData(), 'ord2');
                self::assertSame(['another' => 1, 'precalculatedTotal' => 3.2], $order3->getSerializedData(), 'ord3');
                self::assertSame([], $order4->getSerializedData(), 'ord4');
            });

        $this->optionalListenerManager->expects(self::once())
            ->method('getDisabledListeners')
            ->willReturn(['some_listener']);
        $this->optionalListenerManager->expects(self::once())
            ->method('disableListeners')
            ->with([
                'oro_dataaudit.listener.send_changed_entities_to_message_queue',
                'oro_search.index_listener',
                'oro_website.indexation_request_listener'
            ]);
        $this->optionalListenerManager->expects(self::once())
            ->method('enableListeners')
            ->with([
                'oro_dataaudit.listener.send_changed_entities_to_message_queue',
                'oro_search.index_listener',
                'oro_website.indexation_request_listener'
            ]);

        $message = new Message();
        $message->setBody(['firstOrderId' => 1, 'lastOrderId' => 3]);

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testBatchProcessingWhenSomeListenersAlreadyDisabled(): void
    {
        $order = self::getOrder(1, 1.1, null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Order::class)
            ->willReturn($entityManager);

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $entityManager->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($qb);
        $qb->expects(self::once())
            ->method('from')
            ->with(Order::class, 'o')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('select')
            ->with('o')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('where')
            ->with('o.id >= :firstOrderId AND o.id <= :lastOrderId')
            ->willReturnSelf();
        $qb->expects(self::exactly(2))
            ->method('setParameter')
            ->withConsecutive(['firstOrderId', 1], ['lastOrderId', 1])
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getResult')
            ->willReturn([$order]);

        $this->totalHelper->expects(self::once())
            ->method('calculateTotal')
            ->with(self::identicalTo($order))
            ->willReturn(self::getOrderTotal(1.2));
        $entityManager->expects(self::once())
            ->method('flush')
            ->willReturnCallback(function () use ($order) {
                self::assertSame(['precalculatedTotal' => 1.2], $order->getSerializedData());
            });

        $this->optionalListenerManager->expects(self::once())
            ->method('getDisabledListeners')
            ->willReturn(['some_listener', 'oro_search.index_listener']);
        $this->optionalListenerManager->expects(self::once())
            ->method('disableListeners')
            ->with([
                'oro_dataaudit.listener.send_changed_entities_to_message_queue',
                'oro_website.indexation_request_listener'
            ]);
        $this->optionalListenerManager->expects(self::once())
            ->method('enableListeners')
            ->with([
                'oro_dataaudit.listener.send_changed_entities_to_message_queue',
                'oro_website.indexation_request_listener'
            ]);

        $message = new Message();
        $message->setBody(['firstOrderId' => 1, 'lastOrderId' => 1]);

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }
}
