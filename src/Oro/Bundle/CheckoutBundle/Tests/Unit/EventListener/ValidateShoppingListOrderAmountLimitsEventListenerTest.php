<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\EventListener\ValidateShoppingListOrderAmountLimitsEventListener;
use Oro\Bundle\CheckoutBundle\Validator\Constraints\OrderAmountLimits;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\Action\Event\ExtendableConditionEvent;
use Oro\Component\Action\Event\ExtendableEventData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidateShoppingListOrderAmountLimitsEventListenerTest extends TestCase
{
    private ValidatorInterface&MockObject $validator;

    private ValidateShoppingListOrderAmountLimitsEventListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->listener = new ValidateShoppingListOrderAmountLimitsEventListener($this->validator);
    }

    public function testOnStartFromShoppingListWhenEntityNotShoppingList(): void
    {
        $context = new ExtendableEventData(['shoppingList' => new \stdClass(), 'checkout' => new Checkout()]);
        $event = new ExtendableConditionEvent($context);

        $this->validator
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->onStartFromShoppingList($event);

        self::assertCount(0, $event->getErrors());
    }

    public function testOnStartFromShoppingListWhenShoppingListMissing(): void
    {
        $context = new ExtendableEventData(['shoppingList' => null]);
        $event = new ExtendableConditionEvent($context);

        $this->validator
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->onStartFromShoppingList($event);

        self::assertCount(0, $event->getErrors());
    }

    public function testOnStartFromShoppingListWhenNoViolations(): void
    {
        $shoppingList = new ShoppingList();
        $context = new ExtendableEventData(['shoppingList' => $shoppingList]);
        $event = new ExtendableConditionEvent($context);

        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with(
                self::identicalTo($shoppingList),
                self::callback(static fn ($constraint) => $constraint instanceof OrderAmountLimits)
            )
            ->willReturn(new ConstraintViolationList());

        $this->listener->onStartFromShoppingList($event);

        self::assertCount(0, $event->getErrors());
    }

    public function testOnStartFromShoppingListWhenHasViolation(): void
    {
        $shoppingList = new ShoppingList();
        $context = new ExtendableEventData(['shoppingList' => $shoppingList]);
        $event = new ExtendableConditionEvent($context);

        $violation = new ConstraintViolation(
            'A minimum order subtotal of $50.00 is required to check out. Please add $40.00 more to proceed.',
            'oro.checkout.frontend.checkout.order_limits.minimum_order_amount_flash',
            ['%amount%' => '$50.00', '%difference%' => '$40.00'],
            $shoppingList,
            null,
            $shoppingList,
            null,
            OrderAmountLimits::MINIMUM_NOT_MET_CODE
        );

        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with(
                self::identicalTo($shoppingList),
                self::callback(static fn ($constraint) => $constraint instanceof OrderAmountLimits)
            )
            ->willReturn(new ConstraintViolationList([$violation]));

        $this->listener->onStartFromShoppingList($event);

        self::assertEquals(
            [
                ['message' => $violation->getMessage(), 'context' => $violation],
            ],
            $event->getErrors()->toArray()
        );
    }
}
