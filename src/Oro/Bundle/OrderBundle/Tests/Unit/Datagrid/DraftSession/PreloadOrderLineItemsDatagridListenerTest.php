<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Datagrid\DraftSession;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\OrmResultBeforeQuery;
use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\OrderBundle\Datagrid\DraftSession\PreloadOrderLineItemsDatagridListener;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\Repository\OrderRepository;
use Oro\Component\DraftSession\Manager\DraftSessionOrmFilterManager;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PreloadOrderLineItemsDatagridListenerTest extends TestCase
{
    private const string DRAFT_SESSION_UUID = 'e9e0d4bd-9d75-4b0b-8d47-3f2c37e2a8f1';

    private const int ORDER_ID = 42;

    private const array DEFAULT_PRELOADING_CONFIG = [
        'lineItems' => [
            'orders' => [],
            'product' => [
                'kitItems' => [],
                'unitPrecisions' => [
                    'unit' => [],
                ],
            ],
            'productUnit' => [],
            'kitItemLineItems' => [
                'kitItem' => [
                    'productUnit' => [],
                    'kitItemProducts' => [
                        'product' => [],
                    ],
                ],
                'product' => [
                    'unitPrecisions' => [
                        'unit' => [],
                    ],
                ],
                'productUnit' => [],
            ],
        ],
    ];

    private ManagerRegistry&MockObject $doctrine;

    private PreloadingManager&MockObject $preloadingManager;

    private DraftSessionOrmFilterManager&MockObject $draftSessionOrmFilterManager;

    private PreloadOrderLineItemsDatagridListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->preloadingManager = $this->createMock(PreloadingManager::class);
        $this->draftSessionOrmFilterManager = $this->createMock(DraftSessionOrmFilterManager::class);

        $this->listener = new PreloadOrderLineItemsDatagridListener(
            $this->doctrine,
            $this->preloadingManager,
            $this->draftSessionOrmFilterManager
        );
    }

    public function testOnResultBeforeQueryPreloadsOrderRelationsWithDefaultConfig(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, self::ORDER_ID);
        $order->addLineItem(new OrderLineItem());

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository
            ->expects(self::once())
            ->method('getOrderWithRelations')
            ->with(self::ORDER_ID)
            ->willReturn($order);

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with(Order::class)
            ->willReturn($orderRepository);

        $this->draftSessionOrmFilterManager
            ->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->draftSessionOrmFilterManager
            ->expects(self::once())
            ->method('disable');

        $this->draftSessionOrmFilterManager
            ->expects(self::once())
            ->method('enable');

        $this->preloadingManager
            ->expects(self::once())
            ->method('preloadInEntities')
            ->with([$order], self::DEFAULT_PRELOADING_CONFIG);

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid
            ->expects(self::atLeastOnce())
            ->method('getParameters')
            ->willReturn(new ParameterBag([
                'draft_session_uuid' => self::DRAFT_SESSION_UUID,
                'order_id' => self::ORDER_ID,
            ]));

        $this->listener->onResultBeforeQuery(
            new OrmResultBeforeQuery($datagrid, $this->createMock(QueryBuilder::class))
        );
    }

    public function testOnResultBeforeQueryUsesConfigProvidedBySetPreloadingConfig(): void
    {
        $customPreloadingConfig = ['lineItems' => ['product' => []]];
        $this->listener->setPreloadingConfig($customPreloadingConfig);

        $order = new Order();
        ReflectionUtil::setId($order, self::ORDER_ID);

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository
            ->expects(self::once())
            ->method('getOrderWithRelations')
            ->with(self::ORDER_ID)
            ->willReturn($order);

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with(Order::class)
            ->willReturn($orderRepository);

        $this->draftSessionOrmFilterManager
            ->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->draftSessionOrmFilterManager
            ->expects(self::once())
            ->method('disable');

        $this->draftSessionOrmFilterManager
            ->expects(self::once())
            ->method('enable');

        $this->preloadingManager
            ->expects(self::once())
            ->method('preloadInEntities')
            ->with([$order], $customPreloadingConfig);

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid
            ->expects(self::atLeastOnce())
            ->method('getParameters')
            ->willReturn(new ParameterBag([
                'draft_session_uuid' => self::DRAFT_SESSION_UUID,
                'order_id' => self::ORDER_ID,
            ]));

        $this->listener->onResultBeforeQuery(
            new OrmResultBeforeQuery($datagrid, $this->createMock(QueryBuilder::class))
        );
    }

    public function testOnResultBeforeQueryKeepsOrmFilterDisabledWhenItWasDisabledBefore(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, self::ORDER_ID);

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository
            ->expects(self::once())
            ->method('getOrderWithRelations')
            ->with(self::ORDER_ID)
            ->willReturn($order);

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with(Order::class)
            ->willReturn($orderRepository);

        $this->draftSessionOrmFilterManager
            ->expects(self::once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->draftSessionOrmFilterManager
            ->expects(self::once())
            ->method('disable');

        $this->draftSessionOrmFilterManager
            ->expects(self::never())
            ->method('enable');

        $this->preloadingManager
            ->expects(self::once())
            ->method('preloadInEntities')
            ->with([$order], self::DEFAULT_PRELOADING_CONFIG);

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid
            ->expects(self::atLeastOnce())
            ->method('getParameters')
            ->willReturn(new ParameterBag([
                'draft_session_uuid' => self::DRAFT_SESSION_UUID,
                'order_id' => self::ORDER_ID,
            ]));

        $this->listener->onResultBeforeQuery(
            new OrmResultBeforeQuery($datagrid, $this->createMock(QueryBuilder::class))
        );
    }

    public function testOnResultBeforeQueryDoesNotPreloadWhenOrderIsNotFound(): void
    {
        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository
            ->expects(self::once())
            ->method('getOrderWithRelations')
            ->with(self::ORDER_ID)
            ->willReturn(null);

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with(Order::class)
            ->willReturn($orderRepository);

        $this->draftSessionOrmFilterManager
            ->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->draftSessionOrmFilterManager
            ->expects(self::once())
            ->method('disable');

        $this->draftSessionOrmFilterManager
            ->expects(self::once())
            ->method('enable');

        $this->preloadingManager
            ->expects(self::never())
            ->method('preloadInEntities');

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid
            ->expects(self::atLeastOnce())
            ->method('getParameters')
            ->willReturn(new ParameterBag([
                'draft_session_uuid' => self::DRAFT_SESSION_UUID,
                'order_id' => self::ORDER_ID,
            ]));

        $this->listener->onResultBeforeQuery(
            new OrmResultBeforeQuery($datagrid, $this->createMock(QueryBuilder::class))
        );
    }

    public function testOnResultBeforeQueryDoesNothingWhenDraftSessionUuidIsEmpty(): void
    {
        $this->doctrine
            ->expects(self::never())
            ->method('getRepository');

        $this->draftSessionOrmFilterManager
            ->expects(self::never())
            ->method('disable');

        $this->preloadingManager
            ->expects(self::never())
            ->method('preloadInEntities');

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid
            ->expects(self::atLeastOnce())
            ->method('getParameters')
            ->willReturn(new ParameterBag(['draft_session_uuid' => '', 'order_id' => self::ORDER_ID]));

        $this->listener->onResultBeforeQuery(
            new OrmResultBeforeQuery($datagrid, $this->createMock(QueryBuilder::class))
        );
    }

    public function testOnResultBeforeQueryDoesNothingWhenOrderIdIsEmpty(): void
    {
        $this->doctrine
            ->expects(self::never())
            ->method('getRepository');

        $this->draftSessionOrmFilterManager
            ->expects(self::never())
            ->method('disable');

        $this->preloadingManager
            ->expects(self::never())
            ->method('preloadInEntities');

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid
            ->expects(self::atLeastOnce())
            ->method('getParameters')
            ->willReturn(new ParameterBag(['draft_session_uuid' => self::DRAFT_SESSION_UUID, 'order_id' => 0]));

        $this->listener->onResultBeforeQuery(
            new OrmResultBeforeQuery($datagrid, $this->createMock(QueryBuilder::class))
        );
    }
}
