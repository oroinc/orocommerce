<?php

namespace Oro\Bundle\ShoppingListBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerAwareInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds a line item to be created to the shopping list entity this line item belongs to.
 * This processor is required because LineItem::setShoppingList()
 * does not add the line item to the shopping list, as result the response
 * of the create line item action does not contains this line item in the included shopping list
 * and the shopping list totals are calculated without this line item.
 */
class AddLineItemToShoppingList implements ProcessorInterface, FeatureCheckerAwareInterface
{
    use FeatureCheckerHolderTrait;

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        /** @var LineItem $lineItem */
        $lineItem = $context->getData();
        $shoppingList = $lineItem->getShoppingList();

        if ($lineItem->getSavedForLaterList() !== null && !$this->isFeaturesEnabled()) {
            return;
        }

        if (null !== $shoppingList) {
            // Ensure the line item is removed when it is moved to "Saved for Later" or removed from it
            $shoppingList->removeSavedForLaterLineItem($lineItem);
            $lineItem->removeSavedForLaterList();

            // Ensures collections are pre-initialized and totals are recalculated when a LineItem is moved between them
            $lineItems = $shoppingList->getLineItems();
            if (!$lineItems->contains($lineItem)) {
                $lineItems->add($lineItem);
            }
        }

        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $savedForLaterList = $lineItem->getSavedForLaterList();
        if (null !== $savedForLaterList) {
            // Ensure the line item is removed when it is moved to "Saved for Later" or removed from it
            $savedForLaterList->removeLineItem($lineItem);
            $lineItem->removeShoppingList();

            // Ensures collections are pre-initialized and totals are recalculated when a LineItem is moved between them
            $savedForLaterLineItems = $savedForLaterList->getSavedForLaterLineItems();
            if (!$savedForLaterLineItems->contains($lineItem)) {
                $savedForLaterLineItems->add($lineItem);
            }
        }
    }
}
