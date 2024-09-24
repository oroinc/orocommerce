<?php

namespace Oro\Bundle\PromotionBundle\RuleFiltration;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Context\OrderContextDataConverter;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Discount\ShippingDiscount;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\PromotionBundle\Model\MultiShippingPromotionData;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use Oro\Bundle\ShippingBundle\Method\MultiShippingMethodProvider;

/**
 * Filters out promotions for a multi-shipping order that does not have sub orders.
 * In this case promotions should be separately filtered for shipping methods selected for each line item.
 */
class MultiShippingFiltrationService extends AbstractSkippableFiltrationService
{
    public function __construct(
        private RuleFiltrationServiceInterface $baseFiltrationService
    ) {
    }

    #[\Override]
    protected function filterRuleOwners(array $ruleOwners, array $context): array
    {
        if (empty($context[OrderContextDataConverter::SUB_ORDERS])
            && !empty($context[ContextDataConverterInterface::LINE_ITEMS])
            && $this->isMultiShipping($context[ContextDataConverterInterface::SHIPPING_METHOD] ?? null)
        ) {
            $filteredRuleOwners = $this->filterMultiShippingRuleOwners($ruleOwners, $context);
            if ($filteredRuleOwners) {
                return $filteredRuleOwners;
            }
        }

        return $this->baseFiltrationService->getFilteredRuleOwners($ruleOwners, $context);
    }

    private function isMultiShipping(?string $shippingMethod): bool
    {
        return MultiShippingMethodProvider::MULTI_SHIPPING_METHOD_IDENTIFIER === $shippingMethod;
    }

    private function filterMultiShippingRuleOwners(array $ruleOwners, array $context): array
    {
        $shippingRuleOwners = [];
        $otherRuleOwners = [];
        foreach ($ruleOwners as $ruleOwner) {
            if ($ruleOwner instanceof PromotionDataInterface
                && ShippingDiscount::NAME === $ruleOwner->getDiscountConfiguration()->getType()
            ) {
                $shippingRuleOwners[] = $ruleOwner;
            } else {
                $otherRuleOwners[] = $ruleOwner;
            }
        }

        $filteredRuleOwners = [];
        if ($otherRuleOwners) {
            $filteredRuleOwners = $this->baseFiltrationService->getFilteredRuleOwners($otherRuleOwners, $context);
        }

        if ($shippingRuleOwners) {
            /** @var DiscountLineItem[] $filteredLineItems */
            $filteredLineItems = $this->filterLineItems($context[ContextDataConverterInterface::LINE_ITEMS]);
            if ($filteredLineItems) {
                $filteredRuleOwners = array_merge(
                    $filteredRuleOwners,
                    $this->filterShippingRuleOwners($filteredLineItems, $shippingRuleOwners, $context)
                );
            }
        }

        return $filteredRuleOwners;
    }

    private function filterShippingRuleOwners(
        array $filteredLineItems,
        array $shippingRuleOwners,
        array $context
    ): array {
        $filteredShippingRuleOwners = [];
        foreach ($filteredLineItems as $lineItem) {
            /** @var OrderLineItem $sourceLineItem */
            $sourceLineItem = $lineItem->getSourceLineItem();

            $lineItemContext = $context;
            $lineItemContext[ContextDataConverterInterface::SHIPPING_METHOD] = $sourceLineItem->getShippingMethod();
            $lineItemContext[ContextDataConverterInterface::SHIPPING_COST] = $sourceLineItem->getShippingCost();
            $lineItemContext[ContextDataConverterInterface::LINE_ITEMS] = [$lineItem];
            $lineItemContext[ContextDataConverterInterface::SUBTOTAL] = $lineItem->getSubtotal();
            unset($lineItemContext[ContextDataConverterInterface::APPLIED_COUPONS]);

            /** @var PromotionDataInterface[] $lineItemRuleOwners */
            $lineItemRuleOwners = $this->baseFiltrationService->getFilteredRuleOwners(
                $shippingRuleOwners,
                $lineItemContext
            );
            foreach ($lineItemRuleOwners as $lineItemRuleOwner) {
                $filteredShippingRuleOwners[] = new MultiShippingPromotionData($lineItemRuleOwner, [$lineItem]);
            }
        }

        return $filteredShippingRuleOwners;
    }

    private function filterLineItems(array $lineItems): array
    {
        $filteredLineItems = [];
        /** @var DiscountLineItem $lineItem */
        foreach ($lineItems as $lineItem) {
            $sourceLineItem = $lineItem->getSourceLineItem();
            if (!$sourceLineItem instanceof OrderLineItem || !$sourceLineItem->getShippingMethod()) {
                $filteredLineItems = [];
                break;
            }

            $filteredLineItems[] = $lineItem;
        }

        return $filteredLineItems;
    }
}
