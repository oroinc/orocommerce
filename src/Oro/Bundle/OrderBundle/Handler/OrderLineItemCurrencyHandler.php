<?php

namespace Oro\Bundle\OrderBundle\Handler;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Symfony\Component\Form\FormInterface;

/**
 * Resets the prices on order items.
 */
class OrderLineItemCurrencyHandler
{
    public function resetLineItemsPrices(FormInterface $orderLineItemsForm, Order $order): void
    {
        /** @var OrderLineItem[] $lineItems */
        $orderLineItems = $order->getLineItems();

        /** @var OrderLineItem $orderLineItem */
        foreach ($orderLineItems as $index => $orderLineItem) {
            // Do not need to reset price if the currency has not changed.
            if ($orderLineItem->getCurrency() !== $orderLineItem->getOrder()->getCurrency()) {
                $orderLineItem->setCurrency(null);
                // Check whether the user has specified the price.
                // When currency is changed, need to set default price, if this price was not specified by the user.
                if ($this->isPriceChanged($orderLineItemsForm, $index)) {
                    continue;
                }

                $orderLineItem->setPrice();
            }
        }
    }

    /**
     * @param FormInterface $formLineItems
     * @param int $index
     *
     * @return bool|null
     */
    private function isPriceChanged(FormInterface $formLineItems, int $index): bool
    {
        if (!$formLineItems->offsetExists($index)) {
            return false;
        }

        /** @var FormInterface $lineItem */
        $lineItem = $formLineItems->offsetGet($index);
        if (!$lineItem->offsetExists('price')) {
            return false;
        }

        /** @var FormInterface $price */
        $price = $lineItem->offsetGet('price');
        if (!$price->offsetExists('is_price_changed')) {
            return false;
        }

        return (bool) $price->offsetGet('is_price_changed')->getData();
    }
}
