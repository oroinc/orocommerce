<?php

namespace Oro\Bundle\CheckoutBundle\Shipping\Method\Chain\Member\Price;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutShippingContextProvider;
use Oro\Bundle\CheckoutBundle\Shipping\Method\Chain\Member\AbstractCheckoutShippingMethodsProviderChainElement;
use Oro\Bundle\ShippingBundle\Provider\Price\ShippingPriceProviderInterface;

/**
 * Provides applicable shipping methods views and shipping prices.
 */
class PriceCheckoutShippingMethodsProviderChainElement extends AbstractCheckoutShippingMethodsProviderChainElement
{
    /**
     * @var ShippingPriceProviderInterface
     */
    private $shippingPriceProvider;

    /**
     * @var CheckoutShippingContextProvider
     */
    private $checkoutShippingContextProvider;

    public function __construct(
        ShippingPriceProviderInterface $shippingPriceProvider,
        CheckoutShippingContextProvider $checkoutShippingContextProvider
    ) {
        $this->shippingPriceProvider = $shippingPriceProvider;
        $this->checkoutShippingContextProvider = $checkoutShippingContextProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getApplicableMethodsViews(Checkout $checkout)
    {
        $successorViews = parent::getApplicableMethodsViews($checkout);

        if (false === $successorViews->isEmpty()) {
            return $successorViews;
        }

        $context = $this->checkoutShippingContextProvider->getContext($checkout);

        return $this->shippingPriceProvider->getApplicableMethodsViews($context);
    }

    /**
     * {@inheritdoc}
     */
    public function getPrice(Checkout $checkout)
    {
        $successorPrice = parent::getPrice($checkout);

        if (null !== $successorPrice) {
            return $successorPrice;
        }

        $context = $this->checkoutShippingContextProvider->getContext($checkout);

        return $this->shippingPriceProvider->getPrice(
            $context,
            $checkout->getShippingMethod(),
            $checkout->getShippingMethodType()
        );
    }
}
