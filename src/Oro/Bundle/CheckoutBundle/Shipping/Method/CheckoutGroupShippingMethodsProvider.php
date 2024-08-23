<?php

namespace Oro\Bundle\CheckoutBundle\Shipping\Method;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\MultiShipping\CheckoutFactoryInterface;
use Oro\Bundle\CheckoutBundle\Manager\MultiShipping\CheckoutLineItemGroupsShippingManager;
use Oro\Bundle\CheckoutBundle\Manager\MultiShipping\CheckoutLineItemGroupsShippingManagerInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ShippingBundle\Method\Provider\Integration\ShippingMethodOrganizationProvider;
use Oro\Bundle\ShippingBundle\Provider\GroupLineItemHelper;
use Oro\Bundle\ShippingBundle\Provider\GroupLineItemHelperInterface;

/**
 * Provides views for all applicable shipping methods and calculate a shipping price
 * for a specific group of checkout line items.
 */
class CheckoutGroupShippingMethodsProvider implements CheckoutGroupShippingMethodsProviderInterface
{
    private CheckoutShippingMethodsProviderInterface $checkoutShippingMethodsProvider;
    private CheckoutLineItemGroupsShippingManagerInterface $checkoutLineItemGroupsShippingManager;
    private CheckoutFactoryInterface $checkoutFactory;
    private GroupLineItemHelperInterface $groupLineItemHelper;
    private ShippingMethodOrganizationProvider $organizationProvider;

    public function __construct(
        CheckoutShippingMethodsProviderInterface $checkoutShippingMethodsProvider,
        CheckoutLineItemGroupsShippingManager $checkoutLineItemGroupsShippingManager,
        CheckoutFactoryInterface $checkoutFactory,
        GroupLineItemHelperInterface $groupLineItemHelper,
        ShippingMethodOrganizationProvider $organizationProvider
    ) {
        $this->checkoutShippingMethodsProvider = $checkoutShippingMethodsProvider;
        $this->checkoutLineItemGroupsShippingManager = $checkoutLineItemGroupsShippingManager;
        $this->checkoutFactory = $checkoutFactory;
        $this->groupLineItemHelper = $groupLineItemHelper;
        $this->organizationProvider = $organizationProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getGroupedApplicableMethodsViews(Checkout $checkout, array $groupedLineItemIds): array
    {
        if (!$groupedLineItemIds) {
            return [];
        }

        $result = [];
        $groupingFieldPath = $this->groupLineItemHelper->getGroupingFieldPath();
        $isLineItemsGroupedByOrganization = $this->groupLineItemHelper->isLineItemsGroupedByOrganization(
            $groupingFieldPath
        );
        foreach ($groupedLineItemIds as $lineItemGroupKey => $lineItemIds) {
            $applicableMethodsViews = [];
            if ($lineItemIds) {
                $lineItems = $this->getLineItems($checkout, $lineItemIds);
                if ($lineItems) {
                    $organization = null;
                    if ($isLineItemsGroupedByOrganization
                        && GroupLineItemHelper::OTHER_ITEMS_KEY !== $lineItemGroupKey
                    ) {
                        $organization = $this->groupLineItemHelper->getGroupingFieldValue(
                            $lineItems[0],
                            $groupingFieldPath
                        );
                    }
                    $applicableMethodsViews = $this->getApplicableMethodsViews($checkout, $lineItems, $organization);
                }
            }
            $result[$lineItemGroupKey] = $applicableMethodsViews;
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrentShippingMethods(Checkout $checkout): array
    {
        return $this->checkoutLineItemGroupsShippingManager->getCheckoutLineItemGroupsShippingData($checkout);
    }

    private function getApplicableMethodsViews(
        Checkout $checkout,
        array $lineItems,
        ?Organization $organization
    ): array {
        $checkoutToGetData = $this->checkoutFactory->createCheckout($checkout, $lineItems);

        if (null === $organization) {
            return $this->checkoutShippingMethodsProvider->getApplicableMethodsViews($checkoutToGetData)->toArray();
        }

        $previousOrganization = $this->organizationProvider->getOrganization();
        $this->organizationProvider->setOrganization($organization);
        try {
            return $this->checkoutShippingMethodsProvider->getApplicableMethodsViews($checkoutToGetData)->toArray();
        } finally {
            $this->organizationProvider->setOrganization($previousOrganization);
        }
    }

    private function getLineItems(Checkout $checkout, array $lineItemIds): array
    {
        $lineItems = [];
        foreach ($lineItemIds as $lineItemId) {
            $allLineItems = $checkout->getLineItems();
            foreach ($allLineItems as $lineItem) {
                if ($lineItem->getId() === $lineItemId) {
                    $lineItems[] = $lineItem;
                    break;
                }
            }
        }

        return $lineItems;
    }
}
