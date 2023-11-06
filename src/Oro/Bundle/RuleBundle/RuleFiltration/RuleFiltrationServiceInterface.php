<?php

namespace Oro\Bundle\RuleBundle\RuleFiltration;

use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;

/**
 * Represents a filtration service for rule owners.
 */
interface RuleFiltrationServiceInterface
{
    /**
     * @param RuleOwnerInterface[] $ruleOwners
     * @param array                $context
     *
     * @return RuleOwnerInterface[]
     */
    public function getFilteredRuleOwners(array $ruleOwners, array $context): array;
}
