<?php

namespace Oro\Bundle\PromotionBundle\RuleFiltration;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Discount\DiscountProductUnitCodeAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Provider\MatchingProductsProvider;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

/**
 * This class filters out promotions which are not applicable to current context (i.e. such promotions cannot be
 * applied to any product of lineItems from context).
 */
class MatchingItemsFiltrationService implements RuleFiltrationServiceInterface
{
    /**
     * @var RuleFiltrationServiceInterface
     */
    private $filtrationService;

    /**
     * @var MatchingProductsProvider
     */
    private $matchingProductsProvider;

    /**
     * @param RuleFiltrationServiceInterface $filtrationService
     * @param MatchingProductsProvider $matchingProductsProvider
     */
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
    public function getFilteredRuleOwners(array $ruleOwners, array $context): array
    {
        $lineItems = $context['lineItems'] ?? [];

        $filteredOwners = $ruleOwners;
        if (!empty($lineItems)) {
            $filteredOwners = array_values(array_filter($ruleOwners, function ($ruleOwner) use ($lineItems) {
                if (!$ruleOwner instanceof Promotion) {
                    return false;
                }

                $matchingProducts = $this->matchingProductsProvider
                    ->getMatchingProducts($ruleOwner->getProductsSegment(), $lineItems);

                $discountOptions = $ruleOwner->getDiscountConfiguration()->getOptions();

                return $this->hasMatchedProductUnit(
                    $lineItems,
                    $matchingProducts,
                    $discountOptions[DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE]
                );
            }));
        }

        return $this->filtrationService->getFilteredRuleOwners($filteredOwners, $context);
    }

    /**
     * @param $lineItems
     * @param array|Product[] $matchingProducts
     * @param string $productUnitCode
     * @return bool
     */
    private function hasMatchedProductUnit(array $lineItems, array $matchingProducts, $productUnitCode)
    {
        $productIds = [];
        foreach ($matchingProducts as $product) {
            $productIds[$product->getId()] = true;
        }

        /** @var DiscountLineItem $lineItem */
        foreach ($lineItems as $lineItem) {
            if (isset($productIds[$lineItem->getProduct()->getId()])
                && $lineItem->getProductUnitCode() === $productUnitCode
            ) {
                return true;
            }
        }

        return false;
    }
}
