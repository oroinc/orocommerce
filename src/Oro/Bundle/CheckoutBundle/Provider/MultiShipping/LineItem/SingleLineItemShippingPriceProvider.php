<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItem;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Factory\MultiShipping\CheckoutFactoryInterface;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutShippingContextProvider;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Provider\Price\ShippingPriceProviderInterface;

/**
 * Calculate shipping price for single line item according to shipping methods and checkout data.
 */
class SingleLineItemShippingPriceProvider implements LineItemShippingPriceProviderInterface
{
    private ShippingPriceProviderInterface $shippingPriceProvider;
    private CheckoutShippingContextProvider $checkoutShippingContextProvider;
    private CheckoutFactoryInterface $checkoutFactory;

    public function __construct(
        ShippingPriceProviderInterface $shippingPriceProvider,
        CheckoutShippingContextProvider $checkoutShippingContextProvider,
        CheckoutFactoryInterface $checkoutFactory
    ) {
        $this->shippingPriceProvider = $shippingPriceProvider;
        $this->checkoutShippingContextProvider = $checkoutShippingContextProvider;
        $this->checkoutFactory = $checkoutFactory;
    }

    public function getPrice(CheckoutLineItem $lineItem): Price
    {
        $checkout = $lineItem->getCheckout();

        // Create new checkout for each line item to use it for line item shipping price calculation.
        $shippingCheckout = $this->checkoutFactory->createCheckout($checkout, [$lineItem]);

        // Update checkout shipping method. Shipping cost should be calculated as shipping cost of checkout with
        // single line item and line item's shipping method and types should be used.
        $shippingCheckout->setShippingMethod($lineItem->getShippingMethod())
            ->setShippingMethodType($lineItem->getShippingMethodType());

        $singleLineItemContext = $this->checkoutShippingContextProvider->getContext($shippingCheckout);

        $lineItemShippingCost = $this->shippingPriceProvider->getPrice(
            $singleLineItemContext,
            $lineItem->getShippingMethod(),
            $lineItem->getShippingMethodType()
        );

        return $lineItemShippingCost ?: $lineItemShippingCost = Price::create(0, $lineItem->getCurrency());
    }
}
