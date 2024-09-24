<?php

namespace Oro\Bundle\PromotionBundle\RuleFiltration;

use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

/**
 * Filters out rule owners that are already present.
 */
class DuplicateFiltrationService extends AbstractSkippableFiltrationService
{
    public function __construct(
        private RuleFiltrationServiceInterface $baseFiltrationService
    ) {
    }

    #[\Override]
    protected function filterRuleOwners(array $ruleOwners, array $context): array
    {
        $filteredRuleOwners = $this->filterDuplicatedRuleOwners($ruleOwners);

        return $this->baseFiltrationService->getFilteredRuleOwners($filteredRuleOwners, $context);
    }

    private function filterDuplicatedRuleOwners(array $ruleOwners): array
    {
        $filteredRuleOwners = [];
        $processedIds = [];
        foreach ($ruleOwners as $ruleOwner) {
            if (!$ruleOwner instanceof PromotionDataInterface) {
                continue;
            }

            if (\array_key_exists($ruleOwner->getId(), $processedIds)) {
                continue;
            }

            $processedIds[$ruleOwner->getId()] = true;
            $filteredRuleOwners[] = $ruleOwner;
        }

        return $filteredRuleOwners;
    }
}
