<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds a {@see OrderProductKitItemLineItem} to be created to the {@see OrderLineItem} entity this item belongs to.
 * This processor is required because OrderProductKitItemLineItem::setLineItem()
 * does not add the {@see OrderProductKitItemLineItem} to the {@see OrderLineItem}.
 * As a result:
 *  - the response of the creation {@see OrderProductKitItemLineItem} action does not contain
 *      this {@see OrderProductKitItemLineItem} in the included {@see OrderLineItem};
 *  - the order totals are calculated without this {@see OrderProductKitItemLineItem}.
 */

class AddKitItemLineItemToLineItem implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        /** @var OrderProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $context->getData();
        $lineItem = $kitItemLineItem->getLineItem();
        if (null !== $lineItem) {
            $kitItemLineItems = $lineItem->getKitItemLineItems();
            if (!$kitItemLineItems->contains($kitItemLineItem)) {
                $kitItemLineItems->add($kitItemLineItem);
            }
        }
    }
}
