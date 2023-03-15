<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItem;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Factory\MultiShipping\CheckoutFactoryInterface;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\DefaultMultipleShippingMethodProvider;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
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
    private DefaultMultipleShippingMethodProvider $multiShippingMethodProvider;
    private CheckoutFactoryInterface $checkoutFactory;
    private ShippingMethodOrganizationProvider $organizationProvider;
    private array $cachedLineItemsShippingMethods = [];

    public function __construct(
        CheckoutShippingMethodsProviderInterface $shippingMethodsProvider,
        DefaultMultipleShippingMethodProvider $multiShippingMethodProvider,
        CheckoutFactoryInterface $checkoutFactory,
        ShippingMethodOrganizationProvider $organizationProvider
    ) {
        $this->shippingMethodsProvider = $shippingMethodsProvider;
        $this->multiShippingMethodProvider = $multiShippingMethodProvider;
        $this->checkoutFactory = $checkoutFactory;
        $this->organizationProvider = $organizationProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function reset(): void
    {
        $this->cachedLineItemsShippingMethods = [];
    }

    /**
     * {@inheritDoc}
     */
    public function getAvailableShippingMethods(CheckoutLineItem $lineItem): array
    {
        $lineItemId = $lineItem->getId();
        if (!isset($this->cachedLineItemsShippingMethods[$lineItemId])) {
            $checkout = $this->checkoutFactory->createCheckout($lineItem->getCheckout(), [$lineItem]);
            $this->organizationProvider->setOrganization($lineItem->getProduct()->getOrganization());
            try {
                $this->cachedLineItemsShippingMethods[$lineItemId] = $this->getApplicableMethodsViews($checkout);
            } finally {
                $this->organizationProvider->setOrganization(null);
            }
        }

        return $this->cachedLineItemsShippingMethods[$lineItemId];
    }

    private function getApplicableMethodsViews(Checkout $checkout): array
    {
        $shippingMethods = $this->shippingMethodsProvider->getApplicableMethodsViews($checkout)->toArray();
        if ($this->multiShippingMethodProvider->hasShippingMethods()) {
            // Configured multi_shipping method should not be available for line items.
            // It should be set for checkout entity only.
            $multipleShippingMethodIdentifiers = $this->multiShippingMethodProvider->getShippingMethods();
            $shippingMethods = array_filter(
                $shippingMethods,
                fn ($identifier) => !\in_array($identifier, $multipleShippingMethodIdentifiers, true),
                ARRAY_FILTER_USE_KEY
            );
        }

        return $shippingMethods;
    }
}
