<?php

namespace Oro\Bundle\OrderBundle\Converter;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\PaymentBundle\Context\LineItem\Factory\PaymentLineItemFromProductLineItemFactoryInterface;

/**
 * Converts order line items to a collection of payment line items.
 */
class BasicOrderPaymentLineItemConverter implements OrderPaymentLineItemConverterInterface
{
    private PaymentLineItemFromProductLineItemFactoryInterface $paymentLineItemFactory;

    public function __construct(
        PaymentLineItemFromProductLineItemFactoryInterface $paymentLineItemFactory
    ) {
        $this->paymentLineItemFactory = $paymentLineItemFactory;
    }

    #[\Override]
    public function convertLineItems(Collection $orderLineItems): Collection
    {
        $orderLineItemsToConvert = [];
        foreach ($orderLineItems as $orderLineItem) {
            if ($orderLineItem->getProductUnit() === null) {
                $orderLineItemsToConvert = [];
                break;
            }

            $orderLineItemsToConvert[] = $orderLineItem;
        }

        return $this->paymentLineItemFactory->createCollection($orderLineItemsToConvert);
    }
}
