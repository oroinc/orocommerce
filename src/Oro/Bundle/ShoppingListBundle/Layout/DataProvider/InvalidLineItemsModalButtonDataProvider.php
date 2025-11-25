<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Layout\DataProvider;

use Oro\Bundle\CheckoutBundle\Resolver\ShoppingListToCheckoutValidationGroupResolver;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerAwareInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\RFPBundle\Resolver\ShoppingListToRequestQuoteValidationGroupResolver;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Provider\InvalidShoppingListLineItemsProvider;

/**
 * Provides visible and check on saved for later line items functionalities for checkout error modal button
 */
class InvalidLineItemsModalButtonDataProvider implements FeatureCheckerAwareInterface
{
    use FeatureCheckerHolderTrait;

    public function __construct(
        private readonly InvalidShoppingListLineItemsProvider $invalidShoppingListLineItemsProvider,
        private readonly OrderLimitLayoutProvider $orderLimitLayoutProvider,
        private readonly ShoppingListToCheckoutValidationGroupResolver $checkoutValidationGroupResolver,
        private readonly ShoppingListToRequestQuoteValidationGroupResolver $requestQuoteValidationGroupResolver,
    ) {
    }

    public function isVisibleCheckoutButton(ShoppingList $shoppingList): bool
    {
        if (!$this->isFeaturesEnabled()) {
            return false;
        }

        return !$shoppingList->getLineItems()->isEmpty() &&
            $this->checkoutValidationGroupResolver->isApplicable() &&
            $this->orderLimitLayoutProvider->isOrderLimitsMet($shoppingList) &&
            $this->invalidShoppingListLineItemsProvider->getInvalidLineItemsIds(
                $shoppingList->getLineItems(),
                ShoppingListToCheckoutValidationGroupResolver::TYPE
            );
    }

    public function isVisibleRfqButton(ShoppingList $shoppingList): bool
    {
        if (!$this->isFeaturesEnabled()) {
            return false;
        }

        if ($shoppingList->getLineItems()->isEmpty() || !$this->requestQuoteValidationGroupResolver->isApplicable()) {
            return false;
        }

        return (bool)$this->invalidShoppingListLineItemsProvider->getInvalidLineItemsIds(
            $shoppingList->getLineItems(),
            ShoppingListToRequestQuoteValidationGroupResolver::TYPE
        );
    }
}
