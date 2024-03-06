<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Factory\MultiShipping\CheckoutFactoryInterface;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutShippingContextProvider;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ShippingBundle\Method\Provider\Integration\ShippingMethodOrganizationProvider;
use Oro\Bundle\ShippingBundle\Provider\Price\ShippingPriceProviderInterface;

/**
 * Calculates a shipping price for specified line items.
 */
class MultiShippingCostCalculator
{
    private ShippingPriceProviderInterface $shippingPriceProvider;
    private CheckoutShippingContextProvider $checkoutShippingContextProvider;
    private CheckoutFactoryInterface $checkoutFactory;
    private ShippingMethodOrganizationProvider $organizationProvider;

    public function __construct(
        ShippingPriceProviderInterface $shippingPriceProvider,
        CheckoutShippingContextProvider $checkoutShippingContextProvider,
        CheckoutFactoryInterface $checkoutFactory,
        ShippingMethodOrganizationProvider $organizationProvider
    ) {
        $this->shippingPriceProvider = $shippingPriceProvider;
        $this->checkoutShippingContextProvider = $checkoutShippingContextProvider;
        $this->checkoutFactory = $checkoutFactory;
        $this->organizationProvider = $organizationProvider;
    }

    /**
     * @param Checkout           $checkout
     * @param CheckoutLineItem[] $lineItems
     * @param string             $shippingMethod
     * @param string             $shippingMethodType
     * @param Organization|null  $organization
     *
     * @return Price|null
     */
    public function calculateShippingPrice(
        Checkout $checkout,
        array $lineItems,
        string $shippingMethod,
        string $shippingMethodType,
        ?Organization $organization = null
    ): ?Price {
        // Create a new checkout to use it for shipping price calculation for the given line items.
        $shippingCheckout = $this->checkoutFactory->createCheckout($checkout, $lineItems);
        // Update the created checkout shipping method. The shipping cost should be calculated
        // as shipping cost of checkout with the given line items and the given shipping method should be used.
        $shippingCheckout->setShippingMethod($shippingMethod);
        $shippingCheckout->setShippingMethodType($shippingMethodType);

        $shippingContext = $this->checkoutShippingContextProvider->getContext($shippingCheckout);

        if (null === $organization) {
            return $this->shippingPriceProvider->getPrice($shippingContext, $shippingMethod, $shippingMethodType);
        }

        $previousOrganization = $this->organizationProvider->getOrganization();
        $this->organizationProvider->setOrganization($organization);
        try {
            return $this->shippingPriceProvider->getPrice($shippingContext, $shippingMethod, $shippingMethodType);
        } finally {
            $this->organizationProvider->setOrganization($previousOrganization);
        }
    }
}
