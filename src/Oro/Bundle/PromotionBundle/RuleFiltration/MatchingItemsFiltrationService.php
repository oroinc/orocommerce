<?php

namespace Oro\Bundle\PromotionBundle\RuleFiltration;

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

        $filteredOwners = [];
        if (!empty($lineItems)) {
            $filteredOwners = array_values(array_filter($ruleOwners, function ($ruleOwner) use ($lineItems) {
                return $ruleOwner instanceof Promotion
                    && $this->matchingProductsProvider
                        ->hasMatchingProducts($ruleOwner->getProductsSegment(), $lineItems);
            }));
        }

        return $this->filtrationService->getFilteredRuleOwners($filteredOwners, $context);
    }
}
