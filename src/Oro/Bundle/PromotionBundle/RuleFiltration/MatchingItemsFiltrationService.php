<?php

namespace Oro\Bundle\PromotionBundle\RuleFiltration;

use Oro\Bundle\ProductBundle\Entity\Product;
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

            $matchingProducts = $this->matchingProductsProvider->getMatchingProducts(
                $ruleOwner->getProductsSegment(),
                $lineItems,
                $ruleOwner instanceof Promotion ? $ruleOwner->getOrganization() : null
            );
            if (!$matchingProducts) {
                continue;
            }

            if (!$this->hasMatchedProductUnit($lineItems, $matchingProducts, $ruleOwner->getDiscountConfiguration())) {
                continue;
            }

            $filteredRuleOwners[] = $ruleOwner;
        }

        return $filteredRuleOwners;
    }

    private function hasMatchedProductUnit(
        array $lineItems,
        array $matchingProducts,
        DiscountConfiguration $discountConfiguration
    ): bool {
        $discountOptions = $discountConfiguration->getOptions();
        if (!\array_key_exists(UnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE, $discountOptions)) {
            // a promotion is not unit aware
            return true;
        }

        $productIds = [];
        /** @var Product $product */
        foreach ($matchingProducts as $product) {
            $productIds[$product->getId()] = true;
        }

        $productUnitCode = $discountOptions[UnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE];
        /** @var DiscountLineItem $lineItem */
        foreach ($lineItems as $lineItem) {
            if ($lineItem->getProduct()
                && isset($productIds[$lineItem->getProduct()->getId()])
                && $lineItem->getProductUnitCode() === $productUnitCode
            ) {
                return true;
            }
        }

        return false;
    }
}
