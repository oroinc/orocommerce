<?php

namespace Oro\Bundle\RuleBundle\RuleFiltration;

/**
 * The base implementation a filtration service for rule owners.
 */
class BasicRuleFiltrationService implements RuleFiltrationServiceInterface
{
    #[\Override]
    public function getFilteredRuleOwners(array $ruleOwners, array $context): array
    {
        return $ruleOwners;
    }
}
