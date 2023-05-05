<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds a line item to be created to the order entity this line item belongs to.
 * This processor is required because OrderLineItem::setOrder()
 * does not add the line item to the order, as result the response
 * of the create line item action does not contains this line item in the included order
 * and the order totals are calculated without this line item.
 */
class AddLineItemToOrder implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        /** @var OrderLineItem $lineItem */
        $lineItem = $context->getData();
        $order = $lineItem->getOrder();
        if (null !== $order) {
            $lineItems = $order->getLineItems();
            if (!$lineItems->contains($lineItem)) {
                $lineItems->add($lineItem);
            }
        }
    }
}
