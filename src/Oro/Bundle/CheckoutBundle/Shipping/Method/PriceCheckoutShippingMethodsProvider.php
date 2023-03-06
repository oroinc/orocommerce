<?php

namespace Oro\Bundle\CheckoutBundle\Shipping\Method;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutShippingContextProvider;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use Oro\Bundle\ShippingBundle\Provider\Price\ShippingPriceProviderInterface;

/**
 * The default implementation of the service that provides views for all applicable shipping methods
 * and calculates a shipping price for a specific checkout.
 */
class PriceCheckoutShippingMethodsProvider implements CheckoutShippingMethodsProviderInterface
{
    private ShippingPriceProviderInterface $shippingPriceProvider;
    private CheckoutShippingContextProvider $checkoutShippingContextProvider;

    public function __construct(
        ShippingPriceProviderInterface $shippingPriceProvider,
        CheckoutShippingContextProvider $checkoutShippingContextProvider
    ) {
        $this->shippingPriceProvider = $shippingPriceProvider;
        $this->checkoutShippingContextProvider = $checkoutShippingContextProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getApplicableMethodsViews(Checkout $checkout): ShippingMethodViewCollection
    {
        return $this->shippingPriceProvider->getApplicableMethodsViews(
            $this->checkoutShippingContextProvider->getContext($checkout)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getPrice(Checkout $checkout): ?Price
    {
        return $this->shippingPriceProvider->getPrice(
            $this->checkoutShippingContextProvider->getContext($checkout),
            $checkout->getShippingMethod(),
            $checkout->getShippingMethodType()
        );
    }
}
