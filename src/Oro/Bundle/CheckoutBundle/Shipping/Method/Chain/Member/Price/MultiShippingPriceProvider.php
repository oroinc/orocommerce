<?php

namespace Oro\Bundle\CheckoutBundle\Shipping\Method\Chain\Member\Price;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutShippingContextProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\DefaultMultipleShippingMethodProvider;
use Oro\Bundle\CheckoutBundle\Shipping\Method\Chain\Member\AbstractCheckoutShippingMethodsProviderChainElement;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;

/**
 * Calculate shipping price for checkout with multiple shipping default method.
 */
class MultiShippingPriceProvider extends AbstractCheckoutShippingMethodsProviderChainElement
{
    private DefaultMultipleShippingMethodProvider $shippingMethodProvider;
    private CheckoutShippingContextProvider $checkoutShippingContextProvider;

    public function __construct(
        DefaultMultipleShippingMethodProvider $shippingMethodProvider,
        CheckoutShippingContextProvider $checkoutShippingContextProvider
    ) {
        $this->shippingMethodProvider = $shippingMethodProvider;
        $this->checkoutShippingContextProvider = $checkoutShippingContextProvider;
    }

    public function getApplicableMethodsViews(Checkout $checkout)
    {
        $successorViews = parent::getApplicableMethodsViews($checkout);

        if (false === $successorViews->isEmpty()) {
            return $successorViews;
        }

        return new ShippingMethodViewCollection();
    }

    public function getPrice(Checkout $checkout)
    {
        $successorPrice = parent::getPrice($checkout);

        if (null !== $successorPrice) {
            return $successorPrice;
        }

        if (!$this->shippingMethodProvider->hasShippingMethods()) {
            return null;
        }

        $shippingMethod = $checkout->getShippingMethod();

        $multiShippingMethod = $this->shippingMethodProvider->getShippingMethod();
        if ($shippingMethod === $multiShippingMethod->getIdentifier()) {
            $shippingType = $multiShippingMethod->getType($checkout->getShippingMethodType());
            $context = $this->checkoutShippingContextProvider->getContext($checkout);

            return $shippingType->calculatePrice($context, [], []);
        }

        return null;
    }
}
