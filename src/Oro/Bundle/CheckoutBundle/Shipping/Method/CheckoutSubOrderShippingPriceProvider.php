<?php

namespace Oro\Bundle\CheckoutBundle\Shipping\Method;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ShippingBundle\Method\Provider\Integration\ShippingMethodOrganizationProvider;

/**
 * Calculates a shipping price for a sub-order represented by the given checkout.
 */
class CheckoutSubOrderShippingPriceProvider
{
    private CheckoutShippingMethodsProviderInterface $checkoutShippingMethodsProvider;
    private ConfigProvider $configProvider;
    private ShippingMethodOrganizationProvider $organizationProvider;

    public function __construct(
        CheckoutShippingMethodsProviderInterface $checkoutShippingMethodsProvider,
        ConfigProvider $configProvider,
        ShippingMethodOrganizationProvider $organizationProvider
    ) {
        $this->checkoutShippingMethodsProvider = $checkoutShippingMethodsProvider;
        $this->configProvider = $configProvider;
        $this->organizationProvider = $organizationProvider;
    }

    public function getPrice(Checkout $checkout, ?Organization $organization = null): ?Price
    {
        if (null === $organization || $this->configProvider->isShippingSelectionByLineItemEnabled()) {
            return $this->checkoutShippingMethodsProvider->getPrice($checkout);
        }

        $previousOrganization = $this->organizationProvider->getOrganization();
        $this->organizationProvider->setOrganization($organization);
        try {
            return $this->checkoutShippingMethodsProvider->getPrice($checkout);
        } finally {
            $this->organizationProvider->setOrganization($previousOrganization);
        }
    }
}
