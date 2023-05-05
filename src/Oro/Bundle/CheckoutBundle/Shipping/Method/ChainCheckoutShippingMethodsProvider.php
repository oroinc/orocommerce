<?php

namespace Oro\Bundle\CheckoutBundle\Shipping\Method;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;

/**
 * Delegates getting applicable shipping method views and calculating a shipping price to child providers.
 */
class ChainCheckoutShippingMethodsProvider implements CheckoutShippingMethodsProviderInterface
{
    /** @var iterable<CheckoutShippingMethodsProviderInterface> */
    private iterable $providers;

    /**
     * @param iterable<CheckoutShippingMethodsProviderInterface> $providers
     */
    public function __construct(iterable $providers)
    {
        $this->providers = $providers;
    }

    /**
     * {@inheritDoc}
     */
    public function getApplicableMethodsViews(Checkout $checkout): ShippingMethodViewCollection
    {
        foreach ($this->providers as $provider) {
            $views = $provider->getApplicableMethodsViews($checkout);
            if (!$views->isEmpty()) {
                return $views;
            }
        }

        return new ShippingMethodViewCollection();
    }

    /**
     * {@inheritDoc}
     */
    public function getPrice(Checkout $checkout): ?Price
    {
        foreach ($this->providers as $provider) {
            $price = $provider->getPrice($checkout);
            if (null !== $price) {
                return $price;
            }
        }

        return null;
    }
}
