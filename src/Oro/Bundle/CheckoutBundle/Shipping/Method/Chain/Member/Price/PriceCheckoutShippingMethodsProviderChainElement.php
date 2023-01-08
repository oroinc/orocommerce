<?php

namespace Oro\Bundle\CheckoutBundle\Shipping\Method\Chain\Member\Price;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutShippingContextProvider;
use Oro\Bundle\CheckoutBundle\Shipping\Method\Chain\Member\AbstractCheckoutShippingMethodsProviderChainElement;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use Oro\Bundle\ShippingBundle\Provider\Price\ShippingPriceProviderInterface;

/**
 * The default implementation of the service that provides views for all applicable shipping methods
 * and calculates a shipping price for a specific checkout.
 */
class PriceCheckoutShippingMethodsProviderChainElement extends AbstractCheckoutShippingMethodsProviderChainElement
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
        $successorViews = parent::getApplicableMethodsViews($checkout);
        if (!$successorViews->isEmpty()) {
            return $successorViews;
        }

        return $this->shippingPriceProvider->getApplicableMethodsViews(
            $this->checkoutShippingContextProvider->getContext($checkout)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getPrice(Checkout $checkout): ?Price
    {
        $successorPrice = parent::getPrice($checkout);
        if (null !== $successorPrice) {
            return $successorPrice;
        }

        return $this->shippingPriceProvider->getPrice(
            $this->checkoutShippingContextProvider->getContext($checkout),
            $checkout->getShippingMethod(),
            $checkout->getShippingMethodType()
        );
    }
}
