<?php

namespace Oro\Bundle\RuleBundle\RuleFiltration;

use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;

/**
 * Filters out rule owner that is marked as stop processing
 * and all rule owners that are followed after this rule owner.
 */
class StopProcessingRuleFiltrationService implements RuleFiltrationServiceInterface
{
    public function __construct(
        private RuleFiltrationServiceInterface $baseFiltrationService
    ) {
    }

    #[\Override]
    public function getFilteredRuleOwners(array $ruleOwners, array $context): array
    {
        $filteredRuleOwners = $this->filterRuleOwners($ruleOwners);

        return $this->baseFiltrationService->getFilteredRuleOwners($filteredRuleOwners, $context);
    }

    private function filterRuleOwners(array $ruleOwners): array
    {
        $filteredRuleOwners = [];
        $sortedRuleOwners = $this->getSortedRuleOwners($ruleOwners);
        foreach ($sortedRuleOwners as $ruleOwner) {
            $filteredRuleOwners[] = $ruleOwner;
            if ($ruleOwner->getRule()->isStopProcessing()) {
                break;
            }
        }

        return $filteredRuleOwners;
    }

    /**
     * @param RuleOwnerInterface[] $ruleOwners
     *
     * @return RuleOwnerInterface[]
     */
    private function getSortedRuleOwners(array $ruleOwners): array
    {
        // error suppressing because of https://bugs.php.net/bug.php?id=50688 - also bugs with phpunit mocks
        @usort($ruleOwners, function (RuleOwnerInterface $a, RuleOwnerInterface $b) {
            return $a->getRule()->getSortOrder() - $b->getRule()->getSortOrder();
        });

        return $ruleOwners;
    }
}
