<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItemsGrouping;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Factory\MultiShipping\CheckoutFactoryInterface;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ShippingBundle\Method\Provider\Integration\ShippingMethodOrganizationProvider;
use Oro\Bundle\ShippingBundle\Provider\GroupLineItemHelper;
use Oro\Bundle\ShippingBundle\Provider\GroupLineItemHelperInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Implements logic to get available shipping methods for a group of line items.
 */
class AvailableLineItemGroupShippingMethodsProvider implements
    LineItemGroupShippingMethodsProviderInterface,
    ResetInterface
{
    private CheckoutShippingMethodsProviderInterface $shippingMethodsProvider;
    private CheckoutFactoryInterface $checkoutFactory;
    private ShippingMethodOrganizationProvider $organizationProvider;
    private GroupLineItemHelperInterface $groupLineItemHelper;
    private array $cachedShippingMethods = [];

    public function __construct(
        CheckoutShippingMethodsProviderInterface $shippingMethodsProvider,
        CheckoutFactoryInterface $checkoutFactory,
        ShippingMethodOrganizationProvider $organizationProvider,
        GroupLineItemHelperInterface $groupLineItemHelper
    ) {
        $this->shippingMethodsProvider = $shippingMethodsProvider;
        $this->checkoutFactory = $checkoutFactory;
        $this->organizationProvider = $organizationProvider;
        $this->groupLineItemHelper = $groupLineItemHelper;
    }

    #[\Override]
    public function reset(): void
    {
        $this->cachedShippingMethods = [];
    }

    #[\Override]
    public function getAvailableShippingMethods(array $lineItems, string $lineItemGroupKey): array
    {
        if (!$lineItems) {
            return [];
        }

        $cacheKey = $this->getCacheKey($lineItems, $lineItemGroupKey);
        if (!isset($this->cachedShippingMethods[$cacheKey])) {
            $this->cachedShippingMethods[$cacheKey] = $this->getApplicableMethodsViews($lineItems, $lineItemGroupKey);
        }

        return $this->cachedShippingMethods[$cacheKey];
    }

    private function getApplicableMethodsViews(array $lineItems, string $lineItemGroupKey): array
    {
        $groupingFieldPath = $this->groupLineItemHelper->getGroupingFieldPath();
        $isLineItemsGroupedByOrganization = $this->groupLineItemHelper->isLineItemsGroupedByOrganization(
            $groupingFieldPath
        );

        $checkout = $this->checkoutFactory->createCheckout($lineItems[0]->getCheckout(), $lineItems);
        $organization = null;
        if ($isLineItemsGroupedByOrganization && GroupLineItemHelper::OTHER_ITEMS_KEY !== $lineItemGroupKey) {
            $organization = $this->groupLineItemHelper->getGroupingFieldValue($lineItems[0], $groupingFieldPath);
        }

        return $this->loadApplicableMethodsViews($checkout, $organization);
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

    private function getCacheKey(array $lineItems, string $lineItemGroupKey): string
    {
        $lineItemIds = [];
        /** @var CheckoutLineItem $lineItem */
        foreach ($lineItems as $lineItem) {
            $lineItemIds[] = $lineItem->getId();
        }
        sort($lineItemIds, SORT_NUMERIC);

        return $lineItemGroupKey . '|' . implode(',', $lineItemIds);
    }
}
