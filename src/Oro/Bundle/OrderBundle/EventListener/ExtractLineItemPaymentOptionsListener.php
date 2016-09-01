<?php

namespace Oro\Bundle\OrderBundle\EventListener;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PaymentBundle\Event\ExtractLineItemPaymentOptionsEvent;

class ExtractLineItemPaymentOptionsListener
{
    /**
     * @param ExtractLineItemPaymentOptionsEvent $event
     */
    public function onExtractLineItemPaymentOptions(ExtractLineItemPaymentOptionsEvent $event)
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

            $options[] = $event->applyKeys($lineItemOptions);
        }

        $event->setOptions($options);
    }
}
