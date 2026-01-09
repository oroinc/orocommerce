<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Calculates total shipping cost value for Multi Shipping integration.
 */
class MultiShippingCostProvider
{
    private const METHOD = 'method';
    private const TYPE = 'type';
    private const AMOUNT = 'amount';

    private ConfigProvider $configProvider;
    private MultiShippingCostCalculator $shippingCostCalculator;
    private GroupLineItemHelperInterface $groupLineItemHelper;

    public function __construct(
        ConfigProvider $configProvider,
        MultiShippingCostCalculator $shippingCostCalculator,
        GroupLineItemHelperInterface $groupLineItemHelper
    ) {
        $this->configProvider = $configProvider;
        $this->shippingCostCalculator = $shippingCostCalculator;
        $this->groupLineItemHelper = $groupLineItemHelper;
    }

    public function getCalculatedMultiShippingCost(Checkout $checkout): float
    {
        return $this->configProvider->isShippingSelectionByLineItemEnabled()
            ? $this->getShippingCostPerLineItem($checkout)
            : $this->getShippingCostPerLineItemGroup($checkout);
    }

    private function getShippingCostPerLineItem(Checkout $checkout): float
    {
        $shippingCost = 0.0;
        $lineItems = $checkout->getLineItems();
        foreach ($lineItems as $lineItem) {
            if (!$lineItem->hasShippingMethodData()) {
                continue;
            }
            $existingShippingCost = $lineItem->getShippingCost();
            if ($existingShippingCost) {
                $shippingCost += $existingShippingCost->getValue();
            } else {
                $shippingCost += $this->calculateShippingCost(
                    $checkout,
                    [$lineItem],
                    $lineItem->getShippingMethod(),
                    $lineItem->getShippingMethodType(),
                    $lineItem->getProduct()?->getOrganization()
                );
            }
        }

        return $shippingCost;
    }

    private function getShippingCostPerLineItemGroup(Checkout $checkout): float
    {
        $shippingCost = 0.0;
        $lineItemGroupShippingData = $checkout->getLineItemGroupShippingData();
        if ($lineItemGroupShippingData) {
            $groupingFieldPath = $this->groupLineItemHelper->getGroupingFieldPath();
            $isLineItemsGroupedByOrganization = $this->groupLineItemHelper->isLineItemsGroupedByOrganization(
                $groupingFieldPath
            );
            $groupedLineItems = $this->groupLineItemHelper->getGroupedLineItems(
                $checkout->getLineItems(),
                $groupingFieldPath
            );
            foreach ($groupedLineItems as $lineItemGroupKey => $lineItems) {
                if (!isset($lineItemGroupShippingData[$lineItemGroupKey])) {
                    continue;
                }
                $shippingData = $lineItemGroupShippingData[$lineItemGroupKey];
                if (isset($shippingData[self::AMOUNT])) {
                    $shippingCost += $shippingData[self::AMOUNT];
                } elseif (isset($shippingData[self::METHOD])) {
                    $organization = null;
                    if (
                        $isLineItemsGroupedByOrganization
                        && GroupLineItemHelper::OTHER_ITEMS_KEY !== $lineItemGroupKey
                    ) {
                        $organization = $this->groupLineItemHelper->getGroupingFieldValue(
                            $lineItems[0],
                            $groupingFieldPath
                        );
                    }
                    $shippingCost += $this->calculateShippingCost(
                        $checkout,
                        $lineItems,
                        $shippingData[self::METHOD],
                        $shippingData[self::TYPE],
                        $organization
                    );
                }
            }
        }

        return $shippingCost;
    }

    private function calculateShippingCost(
        Checkout $checkout,
        array $lineItems,
        string $shippingMethod,
        string $shippingMethodType,
        ?Organization $organization = null
    ): float {
        $shippingPrice = $this->shippingCostCalculator->calculateShippingPrice(
            $checkout,
            $lineItems,
            $shippingMethod,
            $shippingMethodType,
            $organization
        );

        return null !== $shippingPrice ? $shippingPrice->getValue() : 0.0;
    }
}
