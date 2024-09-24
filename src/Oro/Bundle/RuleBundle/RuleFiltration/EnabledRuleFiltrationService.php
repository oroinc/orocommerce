<?php

namespace Oro\Bundle\RuleBundle\RuleFiltration;

use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;

/**
 * Filters out disabled rule owners.
 */
class EnabledRuleFiltrationService implements RuleFiltrationServiceInterface
{
    public function __construct(
        private RuleFiltrationServiceInterface $baseFiltrationService
    ) {
    }

    #[\Override]
    public function getFilteredRuleOwners(array $ruleOwners, array $context): array
    {
        $filteredRuleOwners = $this->getEnabledRuleOwners($ruleOwners);

        return $this->baseFiltrationService->getFilteredRuleOwners($filteredRuleOwners, $context);
    }

    private function getEnabledRuleOwners(array $ruleOwners): array
    {
        $enabledRuleOwners = [];
        /** @var RuleOwnerInterface $ruleOwner */
        foreach ($ruleOwners as $ruleOwner) {
            if ($ruleOwner->getRule()->isEnabled()) {
                $enabledRuleOwners[] = $ruleOwner;
            }
        }

        return $enabledRuleOwners;
    }
}
