<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Operation;

use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * Creates an Order draft from the given Shopping List.
 */
class CreateOrderDraftFromShoppingList
{
    public function __construct(
        private readonly OrderDraftManager $orderDraftManager,
    ) {
    }

    public function createOrderDraftFromShoppingList(ShoppingList $shoppingList): Order
    {
        $draftSessionUuid = UUIDGenerator::v4();

        $order = $this->orderDraftManager->saveToEntityDraft($shoppingList, $draftSessionUuid);
        assert($order instanceof Order);

        return $order;
    }
}
