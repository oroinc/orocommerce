<?php

namespace Oro\Bundle\CheckoutBundle\Shipping\Method;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutShippingContextProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\DefaultMultipleShippingMethodProvider;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;

/**
 * Calculates a shipping price for a checkout with Multi Shipping method.
 */
class MultiShippingCheckoutShippingMethodsProvider implements CheckoutShippingMethodsProviderInterface
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

    /**
     * {@inheritDoc}
     */
    public function getApplicableMethodsViews(Checkout $checkout): ShippingMethodViewCollection
    {
        return new ShippingMethodViewCollection();
    }

    /**
     * {@inheritDoc}
     */
    public function getPrice(Checkout $checkout): ?Price
    {
        if (!$this->shippingMethodProvider->hasShippingMethods()) {
            return null;
        }

        $multiShippingMethod = $this->shippingMethodProvider->getShippingMethod();
        if ($checkout->getShippingMethod() !== $multiShippingMethod->getIdentifier()) {
            return null;
        }

        return $multiShippingMethod->getType($checkout->getShippingMethodType())->calculatePrice(
            $this->checkoutShippingContextProvider->getContext($checkout),
            [],
            []
        );
    }
}
