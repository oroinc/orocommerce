<?php

namespace Oro\Bundle\CheckoutBundle\Shipping\Method\Chain\Member;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;

/**
 * The base class for services that provide views for all applicable shipping methods and calculate a shipping price
 * for a specific checkout.
 */
abstract class AbstractCheckoutShippingMethodsProviderChainElement implements CheckoutShippingMethodsProviderInterface
{
    private ?CheckoutShippingMethodsProviderInterface $checkoutShippingMethodsProvider = null;

    public function setSuccessor(CheckoutShippingMethodsProviderInterface $checkoutShippingMethodsProvider): void
    {
        $this->checkoutShippingMethodsProvider = $checkoutShippingMethodsProvider;
    }

    protected function getSuccessor(): ?CheckoutShippingMethodsProviderInterface
    {
        return $this->checkoutShippingMethodsProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getApplicableMethodsViews(Checkout $checkout): ShippingMethodViewCollection
    {
        if (null === $this->getSuccessor()) {
            return new ShippingMethodViewCollection();
        }

        return $this->getSuccessor()->getApplicableMethodsViews($checkout);
    }

    /**
     * {@inheritDoc}
     */
    public function getPrice(Checkout $checkout): ?Price
    {
        if (null === $this->getSuccessor()) {
            return null;
        }

        return $this->getSuccessor()->getPrice($checkout);
    }
}
