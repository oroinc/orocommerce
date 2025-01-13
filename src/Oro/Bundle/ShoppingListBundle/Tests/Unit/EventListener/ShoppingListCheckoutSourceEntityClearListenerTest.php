<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Oro\Bundle\CheckoutBundle\Event\CheckoutSourceEntityClearEvent;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\EventListener\ShoppingListCheckoutSourceEntityClearListener;
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;
use PHPUnit\Framework\TestCase;

class ShoppingListCheckoutSourceEntityClearListenerTest extends TestCase
{
    private ShoppingListCheckoutSourceEntityClearListener $listener;

    protected function setUp(): void
    {
        $this->listener = new ShoppingListCheckoutSourceEntityClearListener();
    }

    public function testOnCheckoutSourceEntityClearWithShoppingList(): void
    {
        $shoppingList = $this->createMock(ShoppingList::class);
        $shoppingList->expects(self::once())
            ->method('setNotes')
            ->with(null);

        $event = $this->createMock(CheckoutSourceEntityClearEvent::class);
        $event->expects(self::once())
            ->method('getCheckoutSourceEntity')
            ->willReturn($shoppingList);

        $this->listener->onCheckoutSourceEntityClear($event);
    }

    public function testOnCheckoutSourceEntityClearWithNonShoppingListEntity(): void
    {
        $nonShoppingListEntity = $this->createMock(CheckoutSourceEntityInterface::class);

        $event = $this->createMock(CheckoutSourceEntityClearEvent::class);
        $event->expects(static::once())
            ->method('getCheckoutSourceEntity')
            ->willReturn($nonShoppingListEntity);

        try {
            $this->listener->onCheckoutSourceEntityClear($event);
            static::assertTrue(true, 'No exceptions were thrown.');
        } catch (\Throwable $e) {
            static::fail('An unexpected exception was thrown: ' . $e->getMessage());
        }
    }
}
