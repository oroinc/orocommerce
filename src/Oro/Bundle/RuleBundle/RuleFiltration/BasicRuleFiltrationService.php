<?php

namespace Oro\Bundle\RuleBundle\RuleFiltration;

class BasicRuleFiltrationService implements RuleFiltrationServiceInterface
{
    /**
     * {@inheritdoc}
     */
    public function getFilteredRuleOwners(array $ruleOwners, array $context)
    {
        return $ruleOwners;
    }
}
