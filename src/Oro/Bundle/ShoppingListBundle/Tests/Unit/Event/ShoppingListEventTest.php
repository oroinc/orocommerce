<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Event;

use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Event\ShoppingListEvent;
use PHPUnit\Framework\TestCase;

final class ShoppingListEventTest extends TestCase
{
    public function testCreate(): void
    {
        $event = new ShoppingListEvent(new ShoppingList());
        self::assertEquals(new ShoppingList(), $event->getShoppingList());
    }

    public function testGettersAndSetters(): void
    {
        $shoppingList = (new ShoppingList())->setLabel('test');

        $event = new ShoppingListEvent(new ShoppingList());
        $event->setShoppingList($shoppingList);

        self::assertEquals($shoppingList, $event->getShoppingList());
    }
}
