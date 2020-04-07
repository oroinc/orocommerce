<?php

namespace Oro\Bundle\CheckoutBundle\Shipping\Method\Chain\Member\Price;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutShippingContextFactory;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\CheckoutShippingContextProvider;
use Oro\Bundle\CheckoutBundle\Shipping\Method\Chain\Member\AbstractCheckoutShippingMethodsProviderChainElement;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
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
     * @var CheckoutShippingContextFactory
     */
    private $checkoutShippingContextFactory;

    /**
     * @var CheckoutShippingContextProvider|null
     */
    private $checkoutShippingContextProvider;

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
     * @param CheckoutShippingContextProvider|null $checkoutShippingContextProvider
     */
    public function setCheckoutShippingContextProvider(
        ?CheckoutShippingContextProvider $checkoutShippingContextProvider
    ): void {
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

        return $this->shippingPriceProvider->getApplicableMethodsViews($this->getShippingContext($checkout));
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

        return $this->shippingPriceProvider->getPrice(
            $this->getShippingContext($checkout),
            $checkout->getShippingMethod(),
            $checkout->getShippingMethodType()
        );
    }

    /**
     * @param Checkout $checkout
     *
     * @return ShippingContextInterface
     */
    private function getShippingContext(Checkout $checkout): ShippingContextInterface
    {
        if ($this->checkoutShippingContextProvider) {
            return $this->checkoutShippingContextProvider->getContext($checkout);
        }

        return $this->checkoutShippingContextFactory->create($checkout);
    }
}
