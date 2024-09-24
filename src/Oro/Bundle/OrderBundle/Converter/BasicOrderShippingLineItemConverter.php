<?php

namespace Oro\Bundle\OrderBundle\Converter;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ShippingBundle\Context\LineItem\Factory\ShippingLineItemFromProductLineItemFactoryInterface;

/**
 * Converts order line items to a collection of shipping line items.
 */
class BasicOrderShippingLineItemConverter implements OrderShippingLineItemConverterInterface
{
    private ShippingLineItemFromProductLineItemFactoryInterface $shippingLineItemFactory;

    public function __construct(
        ShippingLineItemFromProductLineItemFactoryInterface $shippingLineItemFactory
    ) {
        $this->shippingLineItemFactory = $shippingLineItemFactory;
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

        return $this->shippingLineItemFactory->createCollection($orderLineItemsToConvert);
    }
}
