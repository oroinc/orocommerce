<?php

namespace Oro\Bundle\ShoppingListBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds a {@see ProductKitItemLineItem} to be created to the {@see LineItem} entity this line item belongs to.
 * This processor is required because ProductKitItemLineItem::setLineItem()
 * does not add the {@see ProductKitItemLineItem} to the {@see LineItem}.
 * As a result:
 *  - the response of the creation {@see ProductKitItemLineItem} action does not contain
 *      this {@see ProductKitItemLineItem} in the included {@see LineItem};
 *  - the shopping list totals are calculated without this {@see ProductKitItemLineItem}.
 */
class AddProductKitItemLineItemToLineItem implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        /** @var ProductKitItemLineItem $productKitItemLineItem */
        $productKitItemLineItem = $context->getData();
        $lineItem = $productKitItemLineItem->getLineItem();
        if ($lineItem !== null) {
            $kitItemLineItems = $lineItem->getKitItemLineItems();
            if (!$kitItemLineItems->contains($productKitItemLineItem)) {
                $kitItemLineItems->add($productKitItemLineItem);
            }
        }
    }
}
