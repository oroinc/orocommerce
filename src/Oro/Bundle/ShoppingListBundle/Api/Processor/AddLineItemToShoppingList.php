<?php

namespace Oro\Bundle\ShoppingListBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
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
class AddLineItemToShoppingList implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        /** @var LineItem $lineItem */
        $lineItem = $context->getData();
        $shoppingList = $lineItem->getShoppingList();
        if (null !== $shoppingList) {
            $lineItems = $shoppingList->getLineItems();
            if (!$lineItems->contains($lineItem)) {
                $lineItems->add($lineItem);
            }
        }
    }
}
