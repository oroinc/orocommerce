<?php

namespace Oro\Bundle\OrderBundle\EventListener;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PaymentBundle\Event\ExtractLineItemPaymentOptionsEvent;
use Oro\Bundle\PaymentBundle\Model\LineItemOptionModel;

class ExtractLineItemPaymentOptionsListener
{
    /**
     * @param ExtractLineItemPaymentOptionsEvent $event
     */
    public function onExtractLineItemPaymentOptions(ExtractLineItemPaymentOptionsEvent $event)
    {
        $entity = $event->getEntity();
        $lineItems = $entity->getLineItems();

        foreach ($lineItems as $lineItem) {
            if (!$lineItem instanceof OrderLineItem) {
                continue;
            }

            $product = $lineItem->getProduct();

            if (!$product) {
                continue;
            }

            $lineItemModel = new LineItemOptionModel();
            $lineItemModel
                ->setName((string)$product->getDefaultName())
                ->setDescription((string)$product->getDefaultShortDescription())
                ->setCost($lineItem->getValue())
                ->setQty((int)$lineItem->getQuantity());

            $event->addModel($lineItemModel);
        }
    }
}
