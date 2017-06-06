<?php

namespace Oro\Bundle\PromotionBundle\RuleFiltration;

use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

class MatchingItemsFiltrationService implements RuleFiltrationServiceInterface
{
    /**
     * {@inheritdoc}
     */
    public function getFilteredRuleOwners(array $ruleOwners, array $context): array
    {
        $filteredOwners = array_values(array_filter($ruleOwners, [$this, 'hasMatchingItems']));

        return $filteredOwners;
    }

    /**
     * @param RuleOwnerInterface $ruleOwner
     * @return bool
     */
    private function hasMatchingItems(RuleOwnerInterface $ruleOwner): bool
    {
        return true;
    }
}
