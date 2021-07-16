<?php

namespace Oro\Bundle\RuleBundle\RuleFiltration;

use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;

class EnabledRuleFiltrationServiceDecorator implements RuleFiltrationServiceInterface
{
    /**
     * @var RuleFiltrationServiceInterface
     */
    private $filtrationService;

    public function __construct(RuleFiltrationServiceInterface $filtrationService)
    {
        $this->filtrationService = $filtrationService;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilteredRuleOwners(array $ruleOwners, array $context)
    {
        $filteredOwners = array_values(array_filter($ruleOwners, [$this, 'isOwnerRuleEnabled']));
        return $this->filtrationService->getFilteredRuleOwners($filteredOwners, $context);
    }

    /**
     * @param RuleOwnerInterface $ruleOwner
     * @return bool
     */
    private function isOwnerRuleEnabled(RuleOwnerInterface $ruleOwner)
    {
        return $ruleOwner->getRule()->isEnabled();
    }
}
