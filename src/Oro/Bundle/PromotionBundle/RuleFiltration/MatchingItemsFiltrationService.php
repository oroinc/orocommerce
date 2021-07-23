<?php

namespace Oro\Bundle\PromotionBundle\RuleFiltration;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Discount\DiscountProductUnitCodeAwareInterface as UnitCodeAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\PromotionBundle\Provider\MatchingProductsProvider;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

/**
 * This class filters out promotions which are not applicable to current context (i.e. such promotions cannot be
 * applied to any product of lineItems from context).
 */
class MatchingItemsFiltrationService extends AbstractSkippableFiltrationService
{
    /**
     * @var RuleFiltrationServiceInterface
     */
    private $filtrationService;

    /**
     * @var MatchingProductsProvider
     */
    private $matchingProductsProvider;

    public function __construct(
        RuleFiltrationServiceInterface $filtrationService,
        MatchingProductsProvider $matchingProductsProvider
    ) {
        $this->filtrationService = $filtrationService;
        $this->matchingProductsProvider = $matchingProductsProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function filterRuleOwners(array $ruleOwners, array $context): array
    {
        $lineItems = $context[ContextDataConverterInterface::LINE_ITEMS] ?? [];

        if (empty($lineItems)) {
            return [];
        }

        $filteredOwners = array_values(array_filter($ruleOwners, function ($ruleOwner) use ($lineItems) {
            if (!$ruleOwner instanceof PromotionDataInterface) {
                return false;
            }

            $discountOptions = $ruleOwner->getDiscountConfiguration()->getOptions();

            $matchingProducts = $this->matchingProductsProvider
                ->getMatchingProducts($ruleOwner->getProductsSegment(), $lineItems);

            if (!$matchingProducts) {
                return false;
            }

            // Skip promotions that are not unit aware
            if (!array_key_exists(UnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE, $discountOptions)) {
                return true;
            }

            return $this->hasMatchedProductUnit(
                $lineItems,
                $matchingProducts,
                $discountOptions[UnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE]
            );
        }));

        return $this->filtrationService->getFilteredRuleOwners($filteredOwners, $context);
    }

    /**
     * @param array $lineItems
     * @param array|Product[] $matchingProducts
     * @param string $productUnitCode
     * @return bool
     */
    private function hasMatchedProductUnit(array $lineItems, array $matchingProducts, $productUnitCode): bool
    {
        $productIds = [];
        foreach ($matchingProducts as $product) {
            $productIds[$product->getId()] = true;
        }

        /** @var DiscountLineItem $lineItem */
        foreach ($lineItems as $lineItem) {
            if ($lineItem->getProduct() && isset($productIds[$lineItem->getProduct()->getId()])
                && $lineItem->getProductUnitCode() === $productUnitCode
            ) {
                return true;
            }
        }

        return false;
    }
}
