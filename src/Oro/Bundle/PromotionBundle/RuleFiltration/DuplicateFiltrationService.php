<?php

namespace Oro\Bundle\PromotionBundle\RuleFiltration;

use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

/**
 * Filter out rule owners that are already present.
 */
class DuplicateFiltrationService implements RuleFiltrationServiceInterface
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
    public function getFilteredRuleOwners(array $ruleOwners, array $context): array
    {
        $appliedIds = [];
        $ruleOwners = array_values(array_filter($ruleOwners, function ($ruleOwner) use (&$appliedIds) {
            if (!$ruleOwner instanceof PromotionDataInterface) {
                return false;
            }

            if (!array_key_exists($ruleOwner->getId(), $appliedIds)) {
                $appliedIds[$ruleOwner->getId()] = true;

                return true;
            }

            return false;
        }));
        unset($appliedIds);

        return $this->filtrationService->getFilteredRuleOwners($ruleOwners, $context);
    }
}
