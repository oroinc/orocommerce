<?php

namespace Oro\Bundle\PromotionBundle\RuleFiltration;

use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\PromotionBundle\Form\Type\PromotionType;
use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

class ScopeFiltrationService extends AbstractSkippableFiltrationService
{
    /**
     * @var RuleFiltrationServiceInterface
     */
    private $filtrationService;

    /**
     * @var ScopeManager
     */
    private $scopeManager;

    public function __construct(RuleFiltrationServiceInterface $filtrationService, ScopeManager $scopeManager)
    {
        $this->filtrationService = $filtrationService;
        $this->scopeManager = $scopeManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function filterRuleOwners(array $ruleOwners, array $context): array
    {
        $criteria = $context[ContextDataConverterInterface::CRITERIA] ?? null;

        $filteredOwners = array_values(array_filter(
            $ruleOwners,
            function ($ruleOwner) use ($criteria) {
                return $this->hasMatchingScope($ruleOwner, $criteria);
            }
        ));

        return $this->filtrationService->getFilteredRuleOwners($filteredOwners, $context);
    }

    /**
     * @param RuleOwnerInterface $ruleOwner
     * @param ScopeCriteria|null $criteria
     *
     * @return bool
     */
    private function hasMatchingScope(RuleOwnerInterface $ruleOwner, $criteria): bool
    {
        if (!$ruleOwner instanceof PromotionDataInterface || !$criteria instanceof ScopeCriteria) {
            return false;
        }

        if ($ruleOwner->getScopes()->isEmpty()) {
            return true;
        }

        foreach ($ruleOwner->getScopes() as $scope) {
            if ($this->scopeManager->isScopeMatchCriteria($scope, $criteria, PromotionType::SCOPE_TYPE)) {
                return true;
            }
        }

        return false;
    }
}
