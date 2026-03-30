<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\DraftSession\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\DraftSession\Provider\OrderDraftSessionUuidProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\Repository\OrderLineItemRepository;
use Oro\Bundle\OrderBundle\Entity\Repository\OrderRepository;
use Oro\Bundle\OrderBundle\EventListener\DraftSession\LoadOrderDraftOnRequestListener;
use Oro\Component\DraftSession\Factory\EntityDraftFactoryInterface;
use Oro\Component\DraftSession\Synchronizer\EntityDraftSynchronizerInterface;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class OrderDraftManagerTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private RequestStack&MockObject $requestStack;
    private OrderDraftSessionUuidProvider&MockObject $draftSessionUuidProvider;
    private EntityDraftFactoryInterface&MockObject $entityDraftFactory;
    private EntityDraftSynchronizerInterface&MockObject $entityDraftSynchronizer;
    private OrderRepository&MockObject $orderRepository;
    private OrderLineItemRepository&MockObject $orderLineItemRepository;
    private OrderDraftManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->draftSessionUuidProvider = $this->createMock(OrderDraftSessionUuidProvider::class);
        $this->entityDraftFactory = $this->createMock(EntityDraftFactoryInterface::class);
        $this->entityDraftSynchronizer = $this->createMock(EntityDraftSynchronizerInterface::class);
        $this->orderRepository = $this->createMock(OrderRepository::class);
        $this->orderLineItemRepository = $this->createMock(OrderLineItemRepository::class);

        $this->manager = new OrderDraftManager(
            $this->doctrine,
            $this->requestStack,
            $this->draftSessionUuidProvider,
            $this->entityDraftFactory,
            $this->entityDraftSynchronizer
        );
    }

    public function testGetDraftSessionUuidDelegatesToProvider(): void
    {
        $expectedUuid = 'test-draft-uuid-123';

        $this->draftSessionUuidProvider
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn($expectedUuid);

        $result = $this->manager->getDraftSessionUuid();

        self::assertSame($expectedUuid, $result);
    }

    public function testGetDraftSessionUuidReturnsNullWhenProviderReturnsNull(): void
    {
        $this->draftSessionUuidProvider
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn(null);

        $result = $this->manager->getDraftSessionUuid();

        self::assertNull($result);
    }

    public function testGetOrderDraftReturnsOrderFromRequestAttributes(): void
    {
        $orderDraft = new Order();
        ReflectionUtil::setId($orderDraft, 123);

        $request = new Request();
        $request->attributes->set(LoadOrderDraftOnRequestListener::ORDER_DRAFT, $orderDraft);

        $this->requestStack
            ->expects(self::once())
            ->method('getMainRequest')
            ->willReturn($request);

        $result = $this->manager->getOrderDraft();

        self::assertSame($orderDraft, $result);
    }

    public function testGetOrderDraftReturnsNullWhenNoMainRequest(): void
    {
        $this->requestStack
            ->expects(self::once())
            ->method('getMainRequest')
            ->willReturn(null);

        $result = $this->manager->getOrderDraft();

        self::assertNull($result);
    }

    public function testGetOrderDraftReturnsNullWhenAttributeNotSet(): void
    {
        $request = new Request();

        $this->requestStack
            ->expects(self::once())
            ->method('getMainRequest')
            ->willReturn($request);

        $result = $this->manager->getOrderDraft();

        self::assertNull($result);
    }

    public function testCreateEntityDraft(): void
    {
        $draftSessionUuid = 'draft-uuid-456';
        $entity = new Order();
        $entityDraft = new Order();
        ReflectionUtil::setId($entityDraft, 789);

        $this->draftSessionUuidProvider
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn($draftSessionUuid);

        $this->entityDraftFactory
            ->expects(self::once())
            ->method('createDraft')
            ->with($entity, $draftSessionUuid)
            ->willReturn($entityDraft);

        $result = $this->manager->createEntityDraft($entity);

        self::assertSame($entityDraft, $result);
    }

    public function testSynchronizeEntityFromDraft(): void
    {
        $draft = new Order();
        $entity = new Order();

        $this->entityDraftSynchronizer
            ->expects(self::once())
            ->method('synchronizeFromDraft')
            ->with($draft, $entity);

        $this->manager->synchronizeEntityFromDraft($draft, $entity);
    }

    public function testSynchronizeEntityToDraft(): void
    {
        $entity = new Order();
        $draft = new Order();

        $this->entityDraftSynchronizer
            ->expects(self::once())
            ->method('synchronizeToDraft')
            ->with($entity, $draft);

        $this->manager->synchronizeEntityToDraft($entity, $draft);
    }

    public function testFindOrderDraftReturnsOrderWhenFound(): void
    {
        $draftSessionUuid = 'draft-session-uuid-456';
        $expectedOrder = new Order();

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with(Order::class)
            ->willReturn($this->orderRepository);

        $this->orderRepository
            ->expects(self::once())
            ->method('getOrderDraftWithRelations')
            ->with($draftSessionUuid)
            ->willReturn($expectedOrder);

        $result = $this->manager->findOrderDraft($draftSessionUuid);

        self::assertSame($expectedOrder, $result);
    }

    public function testFindOrderDraftReturnsNullWhenNotFound(): void
    {
        $draftSessionUuid = 'draft-session-uuid-not-found';

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with(Order::class)
            ->willReturn($this->orderRepository);

        $this->orderRepository
            ->expects(self::once())
            ->method('getOrderDraftWithRelations')
            ->with($draftSessionUuid)
            ->willReturn(null);

        $result = $this->manager->findOrderDraft($draftSessionUuid);

        self::assertNull($result);
    }

    public function testFindOrderDraftUsesProviderWhenNullUuidPassed(): void
    {
        $providerUuid = 'provider-uuid-123';
        $expectedOrder = new Order();

        $this->draftSessionUuidProvider
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn($providerUuid);

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with(Order::class)
            ->willReturn($this->orderRepository);

        $this->orderRepository
            ->expects(self::once())
            ->method('getOrderDraftWithRelations')
            ->with($providerUuid)
            ->willReturn($expectedOrder);

        $result = $this->manager->findOrderDraft(null);

        self::assertSame($expectedOrder, $result);
    }

    public function testFindOrderLineItemDraftReturnsLineItemWhenFound(): void
    {
        $orderLineItemId = 42;
        $draftSessionUuid = 'draft-session-uuid-789';
        $orderLineItem = new OrderLineItem();
        ReflectionUtil::setId($orderLineItem, $orderLineItemId);
        $expectedLineItem = new OrderLineItem();

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with(OrderLineItem::class)
            ->willReturn($this->orderLineItemRepository);

        $this->orderLineItemRepository
            ->expects(self::once())
            ->method('findOrderLineItemDraftWithRelations')
            ->with($orderLineItemId, $draftSessionUuid)
            ->willReturn($expectedLineItem);

        $result = $this->manager->findOrderLineItemDraft($orderLineItem, $draftSessionUuid);

        self::assertSame($expectedLineItem, $result);
    }

    public function testFindOrderLineItemDraftReturnsNullWhenNotFound(): void
    {
        $orderLineItemId = 99;
        $draftSessionUuid = 'draft-session-uuid-not-found';
        $orderLineItem = new OrderLineItem();
        ReflectionUtil::setId($orderLineItem, $orderLineItemId);

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with(OrderLineItem::class)
            ->willReturn($this->orderLineItemRepository);

        $this->orderLineItemRepository
            ->expects(self::once())
            ->method('findOrderLineItemDraftWithRelations')
            ->with($orderLineItemId, $draftSessionUuid)
            ->willReturn(null);

        $result = $this->manager->findOrderLineItemDraft($orderLineItem, $draftSessionUuid);

        self::assertNull($result);
    }

    public function testFindOrderLineItemDraftUsesProviderWhenNullUuidPassed(): void
    {
        $orderLineItemId = 7;
        $providerUuid = 'provider-uuid-456';
        $orderLineItem = new OrderLineItem();
        ReflectionUtil::setId($orderLineItem, $orderLineItemId);
        $expectedLineItem = new OrderLineItem();

        $this->draftSessionUuidProvider
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn($providerUuid);

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with(OrderLineItem::class)
            ->willReturn($this->orderLineItemRepository);

        $this->orderLineItemRepository
            ->expects(self::once())
            ->method('findOrderLineItemDraftWithRelations')
            ->with($orderLineItemId, $providerUuid)
            ->willReturn($expectedLineItem);

        $result = $this->manager->findOrderLineItemDraft($orderLineItem);

        self::assertSame($expectedLineItem, $result);
    }

    public function testFindOrderLineItemDraftUsesDraftIdForNewEntity(): void
    {
        $draftId = 555;
        $draftSessionUuid = 'draft-session-uuid-new';
        $orderLineItem = new OrderLineItem();
        $orderLineItemDraft = new OrderLineItem();
        ReflectionUtil::setId($orderLineItemDraft, $draftId);

        $orderLineItem->addDraft($orderLineItemDraft);

        $expectedLineItem = new OrderLineItem();

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with(OrderLineItem::class)
            ->willReturn($this->orderLineItemRepository);

        $this->orderLineItemRepository
            ->expects(self::once())
            ->method('findOrderLineItemDraftWithRelations')
            ->with($draftId, $draftSessionUuid)
            ->willReturn($expectedLineItem);

        $result = $this->manager->findOrderLineItemDraft($orderLineItem, $draftSessionUuid);

        self::assertSame($expectedLineItem, $result);
    }

    public function testCreateOrderLineItemDraft(): void
    {
        $draftSessionUuid = 'draft-uuid-create';
        $orderDraft = new Order();
        $orderLineItem = new OrderLineItem();
        $orderLineItemDraft = new OrderLineItem();

        $this->draftSessionUuidProvider
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn($draftSessionUuid);

        $this->entityDraftFactory
            ->expects(self::once())
            ->method('createDraft')
            ->with($orderLineItem, $draftSessionUuid)
            ->willReturn($orderLineItemDraft);

        $result = $this->manager->createOrderLineItemDraft($orderDraft, $orderLineItem);

        self::assertSame($orderLineItemDraft, $result);
        self::assertTrue($orderDraft->getLineItems()->contains($orderLineItemDraft));
    }

    public function testCreateOrderLineItemDraftWithoutOrderLineItem(): void
    {
        $draftSessionUuid = 'draft-uuid-create-new';
        $orderDraft = new Order();
        $orderLineItemDraft = new OrderLineItem();

        $this->draftSessionUuidProvider
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn($draftSessionUuid);

        $this->entityDraftFactory
            ->expects(self::once())
            ->method('createDraft')
            ->with(self::isInstanceOf(OrderLineItem::class), $draftSessionUuid)
            ->willReturn($orderLineItemDraft);

        $result = $this->manager->createOrderLineItemDraft($orderDraft);

        self::assertSame($orderLineItemDraft, $result);
        self::assertTrue($orderDraft->getLineItems()->contains($orderLineItemDraft));
    }

    public function testCreateOrderLineItemDraftWithExplicitUuid(): void
    {
        $explicitUuid = 'explicit-uuid-789';
        $orderDraft = new Order();
        $orderLineItem = new OrderLineItem();
        $orderLineItemDraft = new OrderLineItem();

        $this->draftSessionUuidProvider
            ->expects(self::never())
            ->method('getDraftSessionUuid');

        $this->entityDraftFactory
            ->expects(self::once())
            ->method('createDraft')
            ->with($orderLineItem, $explicitUuid)
            ->willReturn($orderLineItemDraft);

        $result = $this->manager->createOrderLineItemDraft($orderDraft, $orderLineItem, $explicitUuid);

        self::assertSame($orderLineItemDraft, $result);
        self::assertTrue($orderDraft->getLineItems()->contains($orderLineItemDraft));
    }

    public function testGetOrderLineItemOrDraftIdReturnsIdForPersistedEntity(): void
    {
        $orderLineItemId = 123;
        $orderLineItem = new OrderLineItem();
        ReflectionUtil::setId($orderLineItem, $orderLineItemId);

        $result = $this->manager->getOrderLineItemOrDraftId($orderLineItem);

        self::assertEquals($orderLineItemId, $result);
    }

    public function testGetOrderLineItemOrDraftIdReturnsDraftIdForNewEntity(): void
    {
        $draftId = 456;
        $orderLineItem = new OrderLineItem();
        $orderLineItemDraft = new OrderLineItem();
        ReflectionUtil::setId($orderLineItemDraft, $draftId);

        $orderLineItem->addDraft($orderLineItemDraft);

        $result = $this->manager->getOrderLineItemOrDraftId($orderLineItem);

        self::assertEquals($draftId, $result);
    }

    public function testGetOrderLineItemOrDraftIdThrowsExceptionWhenNoDraftForNewEntity(): void
    {
        $orderLineItem = new OrderLineItem();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Entity draft is expected to be present for a new order line item.');

        $this->manager->getOrderLineItemOrDraftId($orderLineItem);
    }
}
