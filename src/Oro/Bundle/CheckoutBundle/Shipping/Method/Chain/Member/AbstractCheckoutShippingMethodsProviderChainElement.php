<?php

namespace Oro\Bundle\CheckoutBundle\Shipping\Method\Chain\Member;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;

abstract class AbstractCheckoutShippingMethodsProviderChainElement implements CheckoutShippingMethodsProviderInterface
{
    /**
     * @var CheckoutShippingMethodsProviderInterface|null
     */
    private $checkoutShippingMethodsProvider;

    public function setSuccessor(
        CheckoutShippingMethodsProviderInterface $checkoutShippingMethodsProvider
    ) {
        $this->checkoutShippingMethodsProvider = $checkoutShippingMethodsProvider;
    }

    /**
     * @return CheckoutShippingMethodsProviderInterface|null
     */
    protected function getSuccessor()
    {
        return $this->checkoutShippingMethodsProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getApplicableMethodsViews(Checkout $checkout)
    {
        if (null === $this->getSuccessor()) {
            return new ShippingMethodViewCollection();
        }

        return $this->getSuccessor()->getApplicableMethodsViews($checkout);
    }

    /**
     * {@inheritdoc}
     */
    public function getPrice(Checkout $checkout)
    {
        if (null === $this->getSuccessor()) {
            return null;
        }

        return $this->getSuccessor()->getPrice($checkout);
    }
}
