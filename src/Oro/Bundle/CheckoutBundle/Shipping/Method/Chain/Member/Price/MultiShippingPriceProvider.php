<?php

namespace Oro\Bundle\CheckoutBundle\Shipping\Method\Chain\Member\Price;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutShippingContextProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\DefaultMultipleShippingMethodProvider;
use Oro\Bundle\CheckoutBundle\Shipping\Method\Chain\Member\AbstractCheckoutShippingMethodsProviderChainElement;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;

/**
 * Provides views for all applicable shipping methods and calculate a shipping price
 * for a checkout with multiple shipping default method.
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

    /**
     * {@inheritDoc}
     */
    public function getApplicableMethodsViews(Checkout $checkout): ShippingMethodViewCollection
    {
        $successorViews = parent::getApplicableMethodsViews($checkout);
        if (!$successorViews->isEmpty()) {
            return $successorViews;
        }

        return new ShippingMethodViewCollection();
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
