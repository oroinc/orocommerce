<?php

namespace Oro\Bundle\OrderBundle\EventListener;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PaymentBundle\Event\ResolveLineItemOptionsEvent;

class ResolveLineItemOptionsListener
{
    /**
     * @param ResolveLineItemOptionsEvent $event
     */
    public function onResolveLineItemOptions(ResolveLineItemOptionsEvent $event)
    {
        $entity = $event->getEntity();
        $lineItems = $entity->getLineItems();
        $options = [];
        foreach ($lineItems as $lineItem) {
            if (!$lineItem instanceof OrderLineItem) {
                continue;
            }

            $product = $lineItem->getProduct();

            if (!$product) {
                continue;
            }

            $lineItemOptions = [
                (string)$product->getDefaultName(),
                (string)$product->getDefaultShortDescription(),
                $lineItem->getValue(),
                (int)$lineItem->getQuantity()
            ];

            $options[] = array_combine($event->getKeys(), $lineItemOptions);
        }

        $event->setOptions($options);
    }
}
