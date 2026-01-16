<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CheckoutBundle\Resolver\ShoppingListToCheckoutValidationGroupResolver;
use Oro\Bundle\RFPBundle\Resolver\ShoppingListToRequestQuoteValidationGroupResolver;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\InvalidLineItemsModalButtonDataProvider;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\OrderLimitLayoutProvider;
use Oro\Bundle\ShoppingListBundle\Provider\InvalidShoppingListLineItemsProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class InvalidLineItemsModalButtonDataProviderTest extends TestCase
{
    private InvalidShoppingListLineItemsProvider&MockObject $provider;
    private OrderLimitLayoutProvider&MockObject $orderLimitLayoutProvider;
    private InvalidLineItemsModalButtonDataProvider $dataProvider;
    private ShoppingListToCheckoutValidationGroupResolver&MockObject $checkoutValidationGroupResolver;
    private ShoppingListToRequestQuoteValidationGroupResolver&MockObject $requestQuoteValidationGroupResolver;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = $this->createMock(InvalidShoppingListLineItemsProvider::class);
        $this->orderLimitLayoutProvider = $this->createMock(OrderLimitLayoutProvider::class);
        $this->checkoutValidationGroupResolver = $this->createMock(
            ShoppingListToCheckoutValidationGroupResolver::class
        );
        $this->requestQuoteValidationGroupResolver = $this->createMock(
            ShoppingListToRequestQuoteValidationGroupResolver::class
        );

        $this->dataProvider = new InvalidLineItemsModalButtonDataProvider(
            $this->provider,
            $this->orderLimitLayoutProvider,
            $this->checkoutValidationGroupResolver,
            $this->requestQuoteValidationGroupResolver
        );
    }

    public function testIsVisibleCheckoutButtonWhenNoLineItems(): void
    {
        self::assertFalse($this->dataProvider->isVisibleCheckoutButton(new ShoppingList()));
    }

    public function testIsVisibleCheckoutButtonWhenOrderLimitReached(): void
    {
        $shoppingList = new ShoppingList();
        $shoppingList->addLineItem(new LineItem());

        $this->checkoutValidationGroupResolver->expects(self::once())
            ->method('isApplicable')
            ->willReturn(true);

        $this->orderLimitLayoutProvider->expects(self::once())
            ->method('isOrderLimitsMet')
            ->willReturn(false);

        self::assertFalse($this->dataProvider->isVisibleCheckoutButton($shoppingList));
    }

    public function testIsVisibleCheckoutButtonWithValidLineItems(): void
    {
        $shoppingList = new ShoppingList();
        $shoppingList->addLineItem(new LineItem());

        $this->checkoutValidationGroupResolver->expects(self::once())
            ->method('isApplicable')
            ->willReturn(true);

        $this->orderLimitLayoutProvider->expects(self::once())
            ->method('isOrderLimitsMet')
            ->willReturn(true);

        $this->provider->expects(self::once())
            ->method('getInvalidLineItemsIds')
            ->with($shoppingList->getLineItems(), ShoppingListToCheckoutValidationGroupResolver::TYPE)
            ->willReturn([]);

        self::assertFalse($this->dataProvider->isVisibleCheckoutButton($shoppingList));
    }

    public function testIsVisibleCheckoutButtonWithInvalidLineItems(): void
    {
        $shoppingList = new ShoppingList();
        $shoppingList->addLineItem(new LineItem());

        $this->checkoutValidationGroupResolver->expects(self::once())
            ->method('isApplicable')
            ->willReturn(true);

        $this->orderLimitLayoutProvider->expects(self::once())
            ->method('isOrderLimitsMet')
            ->willReturn(true);

        $this->provider->expects(self::once())
            ->method('getInvalidLineItemsIds')
            ->with($shoppingList->getLineItems(), ShoppingListToCheckoutValidationGroupResolver::TYPE)
            ->willReturn([1]);

        self::assertTrue($this->dataProvider->isVisibleCheckoutButton($shoppingList));
    }

    public function testIsVisibleCheckoutButtonDeniedByAcl(): void
    {
        $shoppingList = new ShoppingList();
        $shoppingList->addLineItem(new LineItem());

        $this->checkoutValidationGroupResolver->expects(self::once())
            ->method('isApplicable')
            ->willReturn(false);

        self::assertFalse($this->dataProvider->isVisibleCheckoutButton($shoppingList));
    }

    public function testIsVisibleCheckoutButtonDeniedByWorkflow(): void
    {
        $shoppingList = new ShoppingList();
        $shoppingList->addLineItem(new LineItem());

        $this->checkoutValidationGroupResolver->expects(self::once())
            ->method('isApplicable')
            ->willReturn(false);

        self::assertFalse($this->dataProvider->isVisibleCheckoutButton($shoppingList));
    }

    public function testIsVisibleRfqButtonWhenNoLineItems(): void
    {
        self::assertFalse($this->dataProvider->isVisibleRfqButton(new ShoppingList()));
    }

    public function testIsVisibleRfqButtonWithValidLineItems(): void
    {
        $shoppingList = new ShoppingList();
        $shoppingList->addLineItem(new LineItem());

        $this->requestQuoteValidationGroupResolver->expects(self::once())
            ->method('isApplicable')
            ->willReturn(true);

        $this->provider->expects(self::once())
            ->method('getInvalidLineItemsIds')
            ->with($shoppingList->getLineItems(), ShoppingListToRequestQuoteValidationGroupResolver::TYPE)
            ->willReturn([]);

        self::assertFalse($this->dataProvider->isVisibleRfqButton($shoppingList));
    }

    public function testIsVisibleRfqButtonWithInvalidLineItems(): void
    {
        $shoppingList = new ShoppingList();
        $shoppingList->addLineItem(new LineItem());

        $this->requestQuoteValidationGroupResolver->expects(self::once())
            ->method('isApplicable')
            ->willReturn(true);

        $this->provider->expects(self::once())
            ->method('getInvalidLineItemsIds')
            ->with($shoppingList->getLineItems(), ShoppingListToRequestQuoteValidationGroupResolver::TYPE)
            ->willReturn([1]);

        self::assertTrue($this->dataProvider->isVisibleRfqButton($shoppingList));
    }

    public function testIsVisibleRfqButtonDeniedByAcl(): void
    {
        $shoppingList = new ShoppingList();
        $shoppingList->addLineItem(new LineItem());

        $this->requestQuoteValidationGroupResolver->expects(self::once())
            ->method('isApplicable')
            ->willReturn(false);

        self::assertFalse($this->dataProvider->isVisibleRfqButton($shoppingList));
    }
}
