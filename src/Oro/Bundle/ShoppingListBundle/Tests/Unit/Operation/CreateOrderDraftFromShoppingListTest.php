<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Operation;

use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Operation\CreateOrderDraftFromShoppingList;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CreateOrderDraftFromShoppingListTest extends TestCase
{
    private OrderDraftManager&MockObject $orderDraftManager;

    private CreateOrderDraftFromShoppingList $operation;

    #[\Override]
    protected function setUp(): void
    {
        $this->orderDraftManager = $this->createMock(OrderDraftManager::class);

        $this->operation = new CreateOrderDraftFromShoppingList(
            $this->orderDraftManager,
        );
    }

    public function testCreateOrderDraftFromShoppingList(): void
    {
        $shoppingList = new ShoppingList();
        $order = new Order();

        $this->orderDraftManager->expects(self::once())
            ->method('saveToEntityDraft')
            ->with(
                self::identicalTo($shoppingList),
                self::isType('string')
            )
            ->willReturn($order);

        self::assertSame($order, $this->operation->createOrderDraftFromShoppingList($shoppingList));
    }

    public function testCreateOrderDraftFromShoppingListGeneratesDistinctDraftSessionUuids(): void
    {
        $shoppingList = new ShoppingList();

        $capturedUuids = [];
        $this->orderDraftManager->expects(self::exactly(2))
            ->method('saveToEntityDraft')
            ->willReturnCallback(function ($entity, string $draftSessionUuid) use (&$capturedUuids) {
                $capturedUuids[] = $draftSessionUuid;

                return new Order();
            });

        $this->operation->createOrderDraftFromShoppingList($shoppingList);
        $this->operation->createOrderDraftFromShoppingList($shoppingList);

        self::assertCount(2, $capturedUuids);
        self::assertNotSame($capturedUuids[0], $capturedUuids[1]);
    }
}
