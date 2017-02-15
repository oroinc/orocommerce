<?php

namespace Oro\Bundle\CheckoutBundle\Shipping\Method\Chain\Member\Price;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutShippingContextFactory;
use Oro\Bundle\CheckoutBundle\Shipping\Method\Chain\Member\AbstractCheckoutShippingMethodsProviderChainElement;
use Oro\Bundle\ShippingBundle\Provider\Price\ShippingPriceProviderInterface;

class PriceCheckoutShippingMethodsProviderChainElement extends AbstractCheckoutShippingMethodsProviderChainElement
{
    /**
     * @var ShippingPriceProviderInterface
     */
    private $shippingPriceProvider;

    /**
     * @var CheckoutShippingContextFactory
     */
    private $checkoutShippingContextFactory;

    /**
     * @param ShippingPriceProviderInterface $shippingPriceProvider
     * @param CheckoutShippingContextFactory $checkoutShippingContextFactory
     */
    public function __construct(
        ShippingPriceProviderInterface $shippingPriceProvider,
        CheckoutShippingContextFactory $checkoutShippingContextFactory
    ) {
        $this->shippingPriceProvider = $shippingPriceProvider;
        $this->checkoutShippingContextFactory = $checkoutShippingContextFactory;
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

        $context = $this->checkoutShippingContextFactory->create($checkout);

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

        $context = $this->checkoutShippingContextFactory->create($checkout);

        return $this->shippingPriceProvider->getPrice(
            $context,
            $checkout->getShippingMethod(),
            $checkout->getShippingMethodType()
        );
    }
}
