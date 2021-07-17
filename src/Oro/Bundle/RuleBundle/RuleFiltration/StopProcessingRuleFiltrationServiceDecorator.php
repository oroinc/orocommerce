<?php

namespace Oro\Bundle\RuleBundle\RuleFiltration;

use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;

class StopProcessingRuleFiltrationServiceDecorator implements RuleFiltrationServiceInterface
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
        $sortedRuleOwners = $this->getSortedRuleOwners($ruleOwners);

        $filteredOwners = [];
        foreach ($sortedRuleOwners as $ruleOwner) {
            $filteredOwners[] = $ruleOwner;
            if ($ruleOwner->getRule()->isStopProcessing()) {
                break;
            }
        }

        return $this->filtrationService->getFilteredRuleOwners($filteredOwners, $context);
    }

    /**
     * @param RuleOwnerInterface[] $ruleOwners
     *
     * @return RuleOwnerInterface[]
     */
    private function getSortedRuleOwners(array $ruleOwners)
    {
        $sortedRuleOwners = $ruleOwners;

        // error suppressing because of https://bugs.php.net/bug.php?id=50688 - also bugs with phpunit mocks
        @usort($sortedRuleOwners, function (RuleOwnerInterface $a, RuleOwnerInterface $b) {
            return $a->getRule()->getSortOrder() - $b->getRule()->getSortOrder();
        });

        return $sortedRuleOwners;
    }
}
