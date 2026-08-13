<?php

namespace Oro\Bundle\PromotionBundle\RuleFiltration;

use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Discount\DiscountProductUnitCodeAwareInterface as UnitCodeAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\PromotionBundle\Provider\MatchingProductsProviderInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

/**
 * Filters out promotions which are not applicable to current context (i.e. such promotions cannot be
 * applied to any product of lineItems from context).
 */
class MatchingItemsFiltrationService extends AbstractSkippableFiltrationService
{
    public function __construct(
        private RuleFiltrationServiceInterface $baseFiltrationService,
        private MatchingProductsProviderInterface $matchingProductsProvider
    ) {
    }

    #[\Override]
    protected function filterRuleOwners(array $ruleOwners, array $context): array
    {
        $lineItems = $context[ContextDataConverterInterface::LINE_ITEMS] ?? [];
        if (empty($lineItems)) {
            return [];
        }

        $filteredRuleOwners = $this->getMatchedRuleOwners($ruleOwners, $lineItems);

        return $this->baseFiltrationService->getFilteredRuleOwners($filteredRuleOwners, $context);
    }

    private function getMatchedRuleOwners(array $ruleOwners, array $lineItems): array
    {
        $filteredRuleOwners = [];
        foreach ($ruleOwners as $ruleOwner) {
            if (!$ruleOwner instanceof PromotionDataInterface) {
                continue;
            }

            $matchingProductIds = $this->matchingProductsProvider->getMatchingProductIds(
                $ruleOwner->getProductsSegment(),
                $lineItems,
                $ruleOwner instanceof Promotion ? $ruleOwner->getOrganization() : null
            );
            if (!$matchingProductIds) {
                continue;
            }

            if (
                !$this->hasMatchedProductUnit(
                    $lineItems,
                    $matchingProductIds,
                    $ruleOwner->getDiscountConfiguration()
                )
            ) {
                continue;
            }

            $filteredRuleOwners[] = $ruleOwner;
        }

        return $filteredRuleOwners;
    }

    /**
     * @param array<DiscountLineItem> $lineItems
     * @param array<int> $matchingProductIds
     * @param DiscountConfiguration $discountConfiguration
     *
     * @return bool
     */
    private function hasMatchedProductUnit(
        array $lineItems,
        array $matchingProductIds,
        DiscountConfiguration $discountConfiguration
    ): bool {
        $discountOptions = $discountConfiguration->getOptions();
        if (!\array_key_exists(UnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE, $discountOptions)) {
            // a promotion is not unit aware
            return true;
        }

        $productIds = array_fill_keys($matchingProductIds, true);

        $productUnitCode = $discountOptions[UnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE];

        foreach ($lineItems as $lineItem) {
            $discountLineItemProduct = $lineItem->getProduct();
            if (
                $discountLineItemProduct
                && $lineItem->getProductUnitCode() === $productUnitCode
                && isset($productIds[$discountLineItemProduct->getId()])
            ) {
                return true;
            }
        }

        return false;
    }
}
