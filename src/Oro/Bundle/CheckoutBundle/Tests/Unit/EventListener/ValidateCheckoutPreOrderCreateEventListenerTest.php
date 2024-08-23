<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\EventListener\ValidateCheckoutPreOrderCreateEventListener;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutValidationGroupsBySourceEntityProvider;
use Oro\Bundle\CheckoutBundle\Tests\Unit\Model\Action\CheckoutSourceStub;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\Action\Event\ExtendableConditionEvent;
use Oro\Component\Action\Event\ExtendableEventData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidateCheckoutPreOrderCreateEventListenerTest extends TestCase
{
    private CheckoutLineItemsProvider $checkoutLineItemsProvider;

    private ValidatorInterface|MockObject $validator;

    private CheckoutValidationGroupsBySourceEntityProvider|MockObject $validationGroupsProvider;

    private ValidateCheckoutPreOrderCreateEventListener $listener;

    protected function setUp(): void
    {
        $this->checkoutLineItemsProvider = $this->createMock(CheckoutLineItemsProvider::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->validationGroupsProvider = $this->createMock(CheckoutValidationGroupsBySourceEntityProvider::class);

        $this->listener = new ValidateCheckoutPreOrderCreateEventListener(
            $this->checkoutLineItemsProvider,
            $this->validator,
            $this->validationGroupsProvider
        );
    }

    public function testOnPreOrderCreateWhenEntityNotCheckout(): void
    {
        $context = new ExtendableEventData(['checkout' => null, 'shoppingList' => new ShoppingList()]);
        $event = new ExtendableConditionEvent($context);

        $this->validator
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->onPreOrderCreate($event);
    }

    public function testOnPreOrderCreateWhenNoLineItems(): void
    {
        $checkout = new Checkout();
        $context = new ExtendableEventData(['checkout' => $checkout, 'shoppingList' => new ShoppingList()]);
        $event = new ExtendableConditionEvent($context);

        $this->checkoutLineItemsProvider
            ->expects(self::once())
            ->method('getCheckoutLineItems')
            ->willReturn(new ArrayCollection());

        $this->validator
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->onPreOrderCreate($event);
    }

    public function testOnPreOrderCreateWhenNoViolations(): void
    {
        $shoppingList = new ShoppingList();
        $checkout = (new Checkout())
            ->setSource((new CheckoutSourceStub())->setShoppingList($shoppingList));
        $context = new ExtendableEventData(['checkout' => $checkout, 'shoppingList' => $shoppingList]);
        $event = new ExtendableConditionEvent($context);

        $validationGroups = new GroupSequence(['Default', 'checkout_pre_order_create%from_alias%']);
        $this->validationGroupsProvider
            ->expects(self::once())
            ->method('getValidationGroupsBySourceEntity')
            ->with([$validationGroups->groups], $shoppingList)
            ->willReturn([$validationGroups]);

        $lineItem1 = new LineItem();
        $lineItem2 = new LineItem();
        $lineItems = new ArrayCollection([$lineItem1, $lineItem2]);
        $this->checkoutLineItemsProvider
            ->expects(self::once())
            ->method('getCheckoutLineItems')
            ->willReturn($lineItems);

        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($lineItems, null, [$validationGroups])
            ->willReturn(new ConstraintViolationList());

        $this->listener->onPreOrderCreate($event);

        self::assertCount(0, $event->getErrors());
    }

    public function testOnPreOrderCreateWhenHasViolations(): void
    {
        $shoppingList = new ShoppingList();
        $checkout = (new Checkout())
            ->setSource((new CheckoutSourceStub())->setShoppingList($shoppingList));
        $context = new ExtendableEventData(['checkout' => $checkout, 'shoppingList' => $shoppingList]);
        $event = new ExtendableConditionEvent($context);

        $validationGroups = new GroupSequence(['Default', 'checkout_pre_order_create%from_alias%']);
        $this->validationGroupsProvider
            ->expects(self::once())
            ->method('getValidationGroupsBySourceEntity')
            ->with([$validationGroups->groups], $shoppingList)
            ->willReturn([$validationGroups]);

        $lineItem1 = new LineItem();
        $lineItem2 = new LineItem();
        $lineItems = new ArrayCollection([$lineItem1, $lineItem2]);
        $this->checkoutLineItemsProvider
            ->expects(self::once())
            ->method('getCheckoutLineItems')
            ->willReturn($lineItems);

        $violation1 = new ConstraintViolation('sample violation1', null, [], $checkout, null, $checkout);
        $violation2 = new ConstraintViolation('sample violation2', null, [], $checkout, null, $checkout);
        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($lineItems, null, [$validationGroups])
            ->willReturn(new ConstraintViolationList([$violation1, $violation2]));

        $this->listener->onPreOrderCreate($event);

        self::assertEquals(
            [
                ['message' => $violation1->getMessage(), 'context' => $violation1],
                ['message' => $violation2->getMessage(), 'context' => $violation2],
            ],
            $event->getErrors()->toArray()
        );
    }
}
