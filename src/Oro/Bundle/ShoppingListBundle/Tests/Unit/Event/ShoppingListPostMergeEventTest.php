<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Event;

use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Event\ShoppingListPostMergeEvent;
use PHPUnit\Framework\TestCase;

final class ShoppingListPostMergeEventTest extends TestCase
{
    public function testCreate(): void
    {
        $currentShoppingList = new ShoppingList();
        $shoppingList = new ShoppingList();

        $event = new ShoppingListPostMergeEvent($currentShoppingList, $shoppingList);

        self::assertEquals('oro_shopping_list.post_merge', $event::NAME);

        self::assertEquals($currentShoppingList, $event->getCurrentShoppingList());
        self::assertEquals($shoppingList, $event->getShoppingList());
    }

    public function testGettersAndSetters(): void
    {
        $currentShoppingList = (new ShoppingList())->setLabel('current');
        $shoppingList = (new ShoppingList())->setLabel('test');

        $event = new ShoppingListPostMergeEvent(new ShoppingList(), new ShoppingList());

        $event->setCurrentShoppingList($currentShoppingList);
        $event->setShoppingList($shoppingList);

        self::assertEquals($currentShoppingList, $event->getCurrentShoppingList());
        self::assertEquals($shoppingList, $event->getShoppingList());
    }
}
