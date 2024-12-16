<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Event;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Event\ShoppingListPostMoveEvent;
use PHPUnit\Framework\TestCase;

final class ShoppingListPostMoveEventTest extends TestCase
{
    public function testCreate(): void
    {
        $visitor = new CustomerVisitor();
        $user = new CustomerUser();
        $shoppingList = new ShoppingList();

        $event = new ShoppingListPostMoveEvent($visitor, $user, $shoppingList);

        self::assertEquals('oro_shopping_list.post_move', $event::NAME);

        self::assertEquals($visitor, $event->getVisitor());
        self::assertEquals($user, $event->getCustomerUser());
        self::assertEquals($shoppingList, $event->getShoppingList());
    }

    public function testGettersAndSetters(): void
    {
        $visitor = (new CustomerVisitor())->setSessionId('test');
        $user = (new CustomerUser())->setEmail('test@test.test');
        $shoppingList = (new ShoppingList())->setLabel('test');

        $event = new ShoppingListPostMoveEvent(new CustomerVisitor(), new CustomerUser(), new ShoppingList());

        $event->setVisitor($visitor);
        $event->setCustomerUser($user);
        $event->setShoppingList($shoppingList);

        self::assertEquals($visitor, $event->getVisitor());
        self::assertEquals($user, $event->getCustomerUser());
        self::assertEquals($shoppingList, $event->getShoppingList());
    }
}
