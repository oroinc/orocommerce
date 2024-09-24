<?php

namespace Oro\Bundle\CheckoutBundle\Manager\MultiShipping;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItem\LineItemShippingMethodsProviderInterface;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ShippingBundle\Provider\GroupLineItemHelperInterface;
use Oro\Bundle\ShippingBundle\Provider\MultiShippingCostCalculator;

/**
 * Implements logic to handle line items shipping data actions.
 */
class CheckoutLineItemsShippingManager implements CheckoutLineItemsShippingManagerInterface
{
    private const METHOD = 'method';
    private const TYPE = 'type';
    private const IDENTIFIER = 'identifier';
    private const TYPES = 'types';

    private LineItemShippingMethodsProviderInterface $lineItemShippingMethodsProvider;
    private CheckoutLineItemsProvider $lineItemsProvider;
    private MultiShippingCostCalculator $shippingCostCalculator;
    private GroupLineItemHelperInterface $groupLineItemHelper;

    public function __construct(
        LineItemShippingMethodsProviderInterface $lineItemShippingMethodsProvider,
        CheckoutLineItemsProvider $lineItemsProvider,
        MultiShippingCostCalculator $shippingCostCalculator,
        GroupLineItemHelperInterface $groupLineItemHelper
    ) {
        $this->lineItemShippingMethodsProvider = $lineItemShippingMethodsProvider;
        $this->lineItemsProvider = $lineItemsProvider;
        $this->shippingCostCalculator = $shippingCostCalculator;
        $this->groupLineItemHelper = $groupLineItemHelper;
    }

    /**
     * Update line items shipping methods from provided data.
     *
     * @param array|null $shippingData ['2BV:item' => ['method' => 'flat_rate_1', 'type' => 'primary'], ... ]
     * @param Checkout   $checkout
     * @param bool       $useDefaults
     */
    #[\Override]
    public function updateLineItemsShippingMethods(
        ?array $shippingData,
        Checkout $checkout,
        bool $useDefaults = false
    ): void {
        $lineItems = $this->lineItemsProvider->getCheckoutLineItems($checkout);
        foreach ($lineItems as $lineItem) {
            $lineItemShippingData = $this->getLineItemShippingData($shippingData, $lineItem);
            if ($useDefaults && !$lineItemShippingData) {
                $lineItemShippingData = $this->getDefaultLineItemShippingMethod($lineItem);
            }
            if ($lineItemShippingData) {
                $lineItem->setShippingMethod($lineItemShippingData[self::METHOD]);
                $lineItem->setShippingMethodType($lineItemShippingData[self::TYPE]);
            }
        }
    }

    /**
     * Build lineItems shipping data.
     *
     * @param Checkout $checkout
     *
     * @return array ['2BV:item' => ['method' => 'flat_rate_1', 'type' => 'primary'], ... ]
     */
    #[\Override]
    public function getCheckoutLineItemsShippingData(Checkout $checkout): array
    {
        $lineItemsShippingData = [];
        $lineItems = $this->lineItemsProvider->getCheckoutLineItems($checkout);
        foreach ($lineItems as $lineItem) {
            $identifier = $this->getLineItemIdentifier($lineItem);
            $lineItemsShippingData[$identifier] = [
                self::METHOD => $lineItem->getShippingMethod(),
                self::TYPE   => $lineItem->getShippingMethodType()
            ];
        }

        return $lineItemsShippingData;
    }

    #[\Override]
    public function updateLineItemsShippingPrices(Checkout $checkout): void
    {
        $groupingFieldPath = $this->groupLineItemHelper->getGroupingFieldPath();
        $isLineItemsGroupedByOrganization = $this->groupLineItemHelper->isLineItemsGroupedByOrganization(
            $groupingFieldPath
        );
        $lineItems = $this->lineItemsProvider->getCheckoutLineItems($checkout);
        foreach ($lineItems as $lineItem) {
            if (!$lineItem->getShippingMethod()) {
                continue;
            }
            $organization = null;
            if ($isLineItemsGroupedByOrganization) {
                $organization = $this->groupLineItemHelper->getGroupingFieldValue($lineItem, $groupingFieldPath);
            }
            $shippingPrice = $this->shippingCostCalculator->calculateShippingPrice(
                $checkout,
                [$lineItem],
                $lineItem->getShippingMethod(),
                $lineItem->getShippingMethodType(),
                $organization
            );
            $lineItem->setShippingEstimateAmount($shippingPrice?->getValue());
            $lineItem->setCurrency($shippingPrice?->getCurrency());
        }
    }

    #[\Override]
    public function getLineItemIdentifier(ProductLineItemInterface $lineItem): string
    {
        $key = implode(':', [$lineItem->getProductSku(), $lineItem->getProductUnitCode()]);
        if ($lineItem instanceof ProductKitItemLineItemsAwareInterface) {
            $key .= ':' . $lineItem->getChecksum();
        }

        return $key;
    }

    private function getDefaultLineItemShippingMethod(CheckoutLineItem $lineItem): array
    {
        $shippingMethods = $this->lineItemShippingMethodsProvider->getAvailableShippingMethods($lineItem);
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

    private function getLineItemShippingData(?array $shippingData, CheckoutLineItem $lineItem): array
    {
        if (!$shippingData) {
            return [];
        }

        return $shippingData[$this->getLineItemIdentifier($lineItem)] ?? [];
    }
}
