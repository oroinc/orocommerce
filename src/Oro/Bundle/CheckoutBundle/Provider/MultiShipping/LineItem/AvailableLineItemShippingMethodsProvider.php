<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItem;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Factory\MultiShipping\CheckoutFactoryInterface;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ShippingBundle\Method\Provider\Integration\ShippingMethodOrganizationProvider;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Implements logic to get available shipping methods for a single line item.
 */
class AvailableLineItemShippingMethodsProvider implements
    LineItemShippingMethodsProviderInterface,
    ResetInterface
{
    private CheckoutShippingMethodsProviderInterface $shippingMethodsProvider;
    private CheckoutFactoryInterface $checkoutFactory;
    private ShippingMethodOrganizationProvider $organizationProvider;
    private array $cachedShippingMethods = [];

    public function __construct(
        CheckoutShippingMethodsProviderInterface $shippingMethodsProvider,
        CheckoutFactoryInterface $checkoutFactory,
        ShippingMethodOrganizationProvider $organizationProvider
    ) {
        $this->shippingMethodsProvider = $shippingMethodsProvider;
        $this->checkoutFactory = $checkoutFactory;
        $this->organizationProvider = $organizationProvider;
    }

    #[\Override]
    public function reset(): void
    {
        $this->cachedShippingMethods = [];
    }

    #[\Override]
    public function getAvailableShippingMethods(CheckoutLineItem $lineItem): array
    {
        $lineItemId = $lineItem->getId();
        if (!isset($this->cachedShippingMethods[$lineItemId])) {
            $this->cachedShippingMethods[$lineItemId] = $this->getApplicableMethodsViews($lineItem);
        }

        return $this->cachedShippingMethods[$lineItemId];
    }

    private function getApplicableMethodsViews(CheckoutLineItem $lineItem): array
    {
        return $this->loadApplicableMethodsViews(
            $this->checkoutFactory->createCheckout($lineItem->getCheckout(), [$lineItem]),
            $lineItem->getProduct()?->getOrganization()
        );
    }

    private function loadApplicableMethodsViews(Checkout $checkout, ?Organization $organization): array
    {
        if (null === $organization) {
            return $this->shippingMethodsProvider->getApplicableMethodsViews($checkout)->toArray();
        }

        $previousOrganization = $this->organizationProvider->getOrganization();
        $this->organizationProvider->setOrganization($organization);
        try {
            return $this->shippingMethodsProvider->getApplicableMethodsViews($checkout)->toArray();
        } finally {
            $this->organizationProvider->setOrganization($previousOrganization);
        }
    }
}
