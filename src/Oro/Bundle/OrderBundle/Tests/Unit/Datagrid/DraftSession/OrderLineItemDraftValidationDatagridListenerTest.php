<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Datagrid\DraftSession;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\OrmResultBeforeQuery;
use Oro\Bundle\OrderBundle\Datagrid\DraftSession\OrderLineItemDraftValidationDatagridListener;
use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\Repository\OrderRepository;
use Oro\Component\DraftSession\Manager\DraftSessionOrmFilterManager;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ContextualValidatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class OrderLineItemDraftValidationDatagridListenerTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;

    private OrderDraftManager&MockObject $orderDraftManager;

    private ValidatorInterface&MockObject $validator;

    private DraftSessionOrmFilterManager&MockObject $draftSessionOrmFilterManager;

    private OrderLineItemDraftValidationDatagridListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->orderDraftManager = $this->createMock(OrderDraftManager::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->draftSessionOrmFilterManager = $this->createMock(DraftSessionOrmFilterManager::class);

        $this->listener = new OrderLineItemDraftValidationDatagridListener(
            $this->doctrine,
            $this->orderDraftManager,
            $this->validator,
            $this->draftSessionOrmFilterManager
        );
    }

    public function testOnResultBeforeQueryDoesNothingWhenDraftSessionUuidIsEmpty(): void
    {
        $parameterBag = new ParameterBag([
            'draft_session_uuid' => '',
            'order_id' => 0,
        ]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid
            ->expects(self::atLeastOnce())
            ->method('getParameters')
            ->willReturn($parameterBag);

        $event = new OrmResultBeforeQuery($datagrid, $queryBuilder);

        $this->draftSessionOrmFilterManager
            ->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->draftSessionOrmFilterManager
            ->expects(self::once())
            ->method('disable');

        $this->orderDraftManager
            ->expects(self::never())
            ->method('loadFromEntityDraft');

        $this->draftSessionOrmFilterManager
            ->expects(self::once())
            ->method('enable');

        $this->listener->onResultBeforeQuery($event);
    }

    public function testOnResultBeforeQueryAddsIsValidExpressionAndPrependsOrderingForInvalidItems(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, 11);

        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 101);
        $order->addLineItem($lineItem);

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository
            ->expects(self::once())
            ->method('getOrderWithRelations')
            ->with(11)
            ->willReturn($order);

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with(Order::class)
            ->willReturn($orderRepository);

        $this->orderDraftManager
            ->expects(self::once())
            ->method('loadFromEntityDraft')
            ->with($order, 'draft-uuid')
            ->willReturn($order);

        $violation = $this->createMock(ConstraintViolationInterface::class);
        $violation
            ->expects(self::once())
            ->method('getPropertyPath')
            ->willReturn('data.lineItems[0].quantity');

        $contextualValidator = $this->createMock(ContextualValidatorInterface::class);
        $contextualValidator
            ->expects(self::once())
            ->method('atPath')
            ->with('data')
            ->willReturnSelf();

        $contextualValidator
            ->expects(self::once())
            ->method('validate')
            ->willReturnSelf();

        $contextualValidator
            ->expects(self::once())
            ->method('getViolations')
            ->willReturn(new ConstraintViolationList([$violation]));

        $this->validator
            ->expects(self::once())
            ->method('startContext')
            ->with($order)
            ->willReturn($contextualValidator);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder
            ->expects(self::once())
            ->method('addSelect')
            ->with('CASE WHEN orderLineItem.id NOT IN (:invalidLineItems) THEN 1 ELSE 0 END AS isValid')
            ->willReturnSelf();

        $queryBuilder
            ->expects(self::once())
            ->method('setParameter')
            ->with('invalidLineItems', [101], Connection::PARAM_INT_ARRAY)
            ->willReturnSelf();

        $queryBuilder
            ->expects(self::once())
            ->method('getDQLPart')
            ->with('orderBy')
            ->willReturn(['orderLineItem.id DESC']);

        $queryBuilder
            ->expects(self::once())
            ->method('resetDQLPart')
            ->with('orderBy')
            ->willReturnSelf();

        $queryBuilder
            ->expects(self::once())
            ->method('orderBy')
            ->with('isValid', 'ASC')
            ->willReturnSelf();

        $queryBuilder
            ->expects(self::once())
            ->method('addOrderBy')
            ->with('orderLineItem.id DESC')
            ->willReturnSelf();

        $parameterBag = new ParameterBag(['draft_session_uuid' => 'draft-uuid', 'order_id' => 11]);
        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid
            ->expects(self::atLeastOnce())
            ->method('getParameters')
            ->willReturn($parameterBag);

        $event = new OrmResultBeforeQuery($datagrid, $queryBuilder);

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

        $this->listener->onResultBeforeQuery($event);
    }

    public function testOnResultBeforeQueryAddsAlwaysValidExpressionWhenNoInvalidItems(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, 12);

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository
            ->expects(self::once())
            ->method('getOrderWithRelations')
            ->with(12)
            ->willReturn($order);

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with(Order::class)
            ->willReturn($orderRepository);

        $this->orderDraftManager
            ->expects(self::once())
            ->method('loadFromEntityDraft')
            ->with($order, 'draft-uuid')
            ->willReturn($order);

        $contextualValidator = $this->createMock(ContextualValidatorInterface::class);
        $contextualValidator
            ->expects(self::once())
            ->method('atPath')
            ->with('data')
            ->willReturnSelf();

        $contextualValidator
            ->expects(self::once())
            ->method('validate')
            ->willReturnSelf();

        $contextualValidator
            ->expects(self::once())
            ->method('getViolations')
            ->willReturn(new ConstraintViolationList([]));

        $this->validator
            ->expects(self::once())
            ->method('startContext')
            ->with($order)
            ->willReturn($contextualValidator);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder
            ->expects(self::once())
            ->method('addSelect')
            ->with("'1' AS isValid")
            ->willReturnSelf();

        $queryBuilder
            ->expects(self::never())
            ->method('setParameter');

        $parameterBag = new ParameterBag(['draft_session_uuid' => 'draft-uuid', 'order_id' => 12]);
        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid
            ->expects(self::atLeastOnce())
            ->method('getParameters')
            ->willReturn($parameterBag);

        $event = new OrmResultBeforeQuery($datagrid, $queryBuilder);

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

        $this->listener->onResultBeforeQuery($event);
    }
}
