<?php

namespace Oro\Bundle\PromotionBundle\RuleFiltration;

use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

class CouponFiltrationService implements RuleFiltrationServiceInterface
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
        $filteredOwners = array_values(array_filter($ruleOwners, [$this, 'isCouponApplied']));

        return $this->filtrationService->getFilteredRuleOwners($filteredOwners, $context);
    }

    /**
     * @param RuleOwnerInterface $ruleOwner
     * @return bool
     */
    private function isCouponApplied(RuleOwnerInterface $ruleOwner): bool
    {
        if ($ruleOwner instanceof Promotion && $ruleOwner->isUseCoupons()) {
            return true;
        }

        return false;
    }
}
