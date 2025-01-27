<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Event;

use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Event\ShoppingListEventPostTransfer;

class ShoppingListEventPostTransferTest extends \PHPUnit\Framework\TestCase
{
    public function testEventProperties(): void
    {
        $shoppingList = $this->createMock(ShoppingList::class);
        $currentShoppingList = $this->createMock(ShoppingList::class);

        $event = new ShoppingListEventPostTransfer($shoppingList, $currentShoppingList);

        $this->assertSame($shoppingList, $event->getShoppingList());
        $this->assertSame($currentShoppingList, $event->getCurrentShoppingList());
    }

    public function testEventName(): void
    {
        $this->assertSame('oro_shopping_list.post_transfer', ShoppingListEventPostTransfer::NAME);
    }
}
