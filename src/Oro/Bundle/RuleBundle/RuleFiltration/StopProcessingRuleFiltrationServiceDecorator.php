<?php

namespace Oro\Bundle\RuleBundle\RuleFiltration;

use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;

class StopProcessingRuleFiltrationServiceDecorator implements RuleFiltrationServiceInterface
{
    /**
     * @var RuleFiltrationServiceInterface
     */
    private $filtrationService;

    /**
     * @param RuleFiltrationServiceInterface $filtrationService
     */
    public function __construct(RuleFiltrationServiceInterface $filtrationService)
    {
        $this->filtrationService = $filtrationService;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilteredRuleOwners(array $ruleOwners, array $context)
    {
        usort($ruleOwners, [$this, 'compareRuleOwnersOrder']);

        $filteredOwners = [];
        foreach ($ruleOwners as $ruleOwner) {
            $filteredOwners[] = $ruleOwner;
            if ($ruleOwner->getRule()->isStopProcessing()) {
                break;
            }
        }

        return $this->filtrationService->getFilteredRuleOwners($filteredOwners, $context);
    }

    /**
     * @param RuleOwnerInterface $a
     * @param RuleOwnerInterface $b
     * @return int
     */
    private function compareRuleOwnersOrder(RuleOwnerInterface $a, RuleOwnerInterface $b)
    {
        return $a->getRule()->getSortOrder() - $b->getRule()->getSortOrder();
    }
}
