<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Event\CheckoutActualizeEvent;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\EventListener\ActualizeCheckoutListener;
use PHPUnit\Framework\TestCase;

class ActualizeCheckoutListenerTest extends TestCase
{
    private ActualizeCheckoutListener $listener;

    protected function setUp(): void
    {
        $this->listener = new ActualizeCheckoutListener();
    }

    public function testOnActualizeWithShoppingListHavingNotes(): void
    {
        $notes = 'Test notes';
        $shoppingList = $this->createMock(ShoppingList::class);
        $sourceCriteria = ['shoppingList' => $shoppingList];
        $checkout = $this->createMock(Checkout::class);

        $shoppingList->expects(self::atLeastOnce())
            ->method('getNotes')
            ->willReturn($notes);

        $checkout->expects(self::once())
            ->method('setCustomerNotes')
            ->with($notes);

        $this->listener->onActualize(new CheckoutActualizeEvent($checkout, $sourceCriteria));
    }

    public function testOnActualizeWithShoppingListWithoutNotes(): void
    {
        $shoppingList = $this->createMock(ShoppingList::class);
        $sourceCriteria = ['shoppingList' => $shoppingList];
        $checkout = $this->createMock(Checkout::class);

        $shoppingList->expects(self::once())
            ->method('getNotes')
            ->willReturn(null);

        $checkout->expects(self::never())
            ->method('setCustomerNotes');

        $this->listener->onActualize(new CheckoutActualizeEvent($checkout, $sourceCriteria));
    }

    public function testOnActualizeWithoutShoppingList(): void
    {
        $checkout = $this->createMock(Checkout::class);

        $checkout->expects(self::never())
            ->method('setCustomerNotes');

        $this->listener->onActualize(new CheckoutActualizeEvent($checkout, []));
    }
}
