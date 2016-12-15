<?php

namespace Oro\Bundle\RuleBundle\RuleFiltration;

use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;

interface RuleFiltrationServiceInterface
{
    /**
     * @param RuleOwnerInterface[]|array $ruleOwners
     * @param array $context
     * @return RuleOwnerInterface[]|array
     */
    public function getFilteredRuleOwners(array $ruleOwners, array $context);
}
