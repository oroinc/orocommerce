<?php

namespace Oro\Bundle\CheckoutBundle\Manager\MultiShipping;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItemsGrouping\LineItemGroupShippingMethodsProviderInterface;
use Oro\Bundle\ShippingBundle\Provider\GroupLineItemHelper;
use Oro\Bundle\ShippingBundle\Provider\GroupLineItemHelperInterface;
use Oro\Bundle\ShippingBundle\Provider\MultiShippingCostCalculator;

/**
 * Implements logic to handle line item groups shipping data actions.
 */
class CheckoutLineItemGroupsShippingManager implements CheckoutLineItemGroupsShippingManagerInterface
{
    private const METHOD = 'method';
    private const TYPE = 'type';
    private const IDENTIFIER = 'identifier';
    private const TYPES = 'types';

    private LineItemGroupShippingMethodsProviderInterface $lineItemGroupShippingMethodsProvider;
    private CheckoutLineItemsProvider $lineItemsProvider;
    private MultiShippingCostCalculator $shippingCostCalculator;
    private GroupLineItemHelperInterface $groupLineItemHelper;

    public function __construct(
        LineItemGroupShippingMethodsProviderInterface $lineItemGroupShippingMethodsProvider,
        CheckoutLineItemsProvider $lineItemsProvider,
        MultiShippingCostCalculator $shippingCostCalculator,
        GroupLineItemHelperInterface $groupLineItemHelper
    ) {
        $this->lineItemGroupShippingMethodsProvider = $lineItemGroupShippingMethodsProvider;
        $this->lineItemsProvider = $lineItemsProvider;
        $this->shippingCostCalculator = $shippingCostCalculator;
        $this->groupLineItemHelper = $groupLineItemHelper;
    }

    /**
     * Updates shipping methods for line item groups from provided data.
     *
     * @param array|null $shippingData ['product.category:1' => ['method' => 'flat_rate_1', 'type' => 'primary'], ... ]
     * @param Checkout   $checkout
     * @param bool       $useDefaults
     */
    #[\Override]
    public function updateLineItemGroupsShippingMethods(
        ?array $shippingData,
        Checkout $checkout,
        bool $useDefaults = false
    ): void {
        $lineItemGroupShipping = $this->getLineItemGroupShipping($checkout->getLineItemGroupShippingData());
        $lineItemGroupShipping->removeAllShippingMethods();

        $groupedLineItems = $this->groupLineItemHelper->getGroupedLineItems(
            $this->lineItemsProvider->getCheckoutLineItems($checkout),
            $this->groupLineItemHelper->getGroupingFieldPath()
        );
        foreach ($groupedLineItems as $lineItemGroupKey => $lineItems) {
            $groupShippingData = $shippingData[$lineItemGroupKey] ?? [];
            if ($useDefaults && !$groupShippingData) {
                $groupShippingData = $this->getDefaultLineItemGroupShippingMethod($lineItems, $lineItemGroupKey);
            }
            if ($groupShippingData) {
                $lineItemGroupShipping->setShippingMethod(
                    $lineItemGroupKey,
                    $groupShippingData[self::METHOD],
                    $groupShippingData[self::TYPE]
                );
            }
        }

        $checkout->setLineItemGroupShippingData($lineItemGroupShipping->toArray());
    }

    /**
     * Gets line item groups shipping data.
     *
     * @param Checkout $checkout
     *
     * @return array ['product.category:1' => ['method' => 'flat_rate_1', 'type' => 'primary'], ... ]
     */
    #[\Override]
    public function getCheckoutLineItemGroupsShippingData(Checkout $checkout): array
    {
        return $this->getLineItemGroupShipping($checkout->getLineItemGroupShippingData())->getShippingMethods();
    }

    #[\Override]
    public function updateLineItemGroupsShippingPrices(Checkout $checkout): void
    {
        $lineItemGroupShipping = $this->getLineItemGroupShipping($checkout->getLineItemGroupShippingData());
        $lineItemGroupShipping->removeAllShippingEstimateAmounts();

        $groupedShippingData = $lineItemGroupShipping->getShippingMethods();
        $groupingFieldPath = $this->groupLineItemHelper->getGroupingFieldPath();
        $isLineItemsGroupedByOrganization = $this->groupLineItemHelper->isLineItemsGroupedByOrganization(
            $groupingFieldPath
        );
        $groupedLineItems = $this->groupLineItemHelper->getGroupedLineItems(
            $this->lineItemsProvider->getCheckoutLineItems($checkout),
            $groupingFieldPath
        );
        foreach ($groupedLineItems as $lineItemGroupKey => $lineItems) {
            $shippingData = $groupedShippingData[$lineItemGroupKey] ?? null;
            if (!$shippingData) {
                continue;
            }
            $organization = null;
            if ($isLineItemsGroupedByOrganization && GroupLineItemHelper::OTHER_ITEMS_KEY !== $lineItemGroupKey) {
                $organization = $this->groupLineItemHelper->getGroupingFieldValue($lineItems[0], $groupingFieldPath);
            }
            $shippingPrice = $this->shippingCostCalculator->calculateShippingPrice(
                $checkout,
                $lineItems,
                $shippingData[self::METHOD],
                $shippingData[self::TYPE],
                $organization
            );
            if (null !== $shippingPrice) {
                $lineItemGroupShipping->setShippingEstimateAmount($lineItemGroupKey, $shippingPrice->getValue());
            }
        }

        $checkout->setLineItemGroupShippingData($lineItemGroupShipping->toArray());
    }

    private function getDefaultLineItemGroupShippingMethod(array $lineItems, string $lineItemGroupKey): array
    {
        $shippingMethods = $this->lineItemGroupShippingMethodsProvider->getAvailableShippingMethods(
            $lineItems,
            $lineItemGroupKey
        );
        if ($shippingMethods) {
            $defaultShippingMethod = reset($shippingMethods);
            $defaultShippingMethodType = isset($defaultShippingMethod[self::TYPES])
                ? reset($defaultShippingMethod[self::TYPES])
                : [];
            if ($defaultShippingMethodType) {
                return [
                    self::METHOD => $defaultShippingMethod[self::IDENTIFIER],
                    self::TYPE   => $defaultShippingMethodType[self::IDENTIFIER]
                ];
            }
        }

        return [];
    }

    private function getLineItemGroupShipping(?array $shippingData): CheckoutLineItemGroupShippingData
    {
        return new CheckoutLineItemGroupShippingData($shippingData);
    }
}
