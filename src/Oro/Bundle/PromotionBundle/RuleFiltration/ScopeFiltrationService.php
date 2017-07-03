<?php

namespace Oro\Bundle\PromotionBundle\RuleFiltration;

use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Form\Type\PromotionType;
use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

class ScopeFiltrationService implements RuleFiltrationServiceInterface
{
    /**
     * @var RuleFiltrationServiceInterface
     */
    private $filtrationService;

    /**
     * @var ScopeManager
     */
    private $scopeManager;

    /**
     * @param RuleFiltrationServiceInterface $filtrationService
     * @param ScopeManager $scopeManager
     */
    public function __construct(RuleFiltrationServiceInterface $filtrationService, ScopeManager $scopeManager)
    {
        $this->filtrationService = $filtrationService;
        $this->scopeManager = $scopeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilteredRuleOwners(array $ruleOwners, array $context): array
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
        if (!$ruleOwner instanceof Promotion || !$criteria instanceof ScopeCriteria) {
            return false;
        }

        foreach ($ruleOwner->getScopes() as $scope) {
            if ($this->scopeManager->isScopeMatchCriteria($scope, $criteria, PromotionType::SCOPE_TYPE)) {
                return true;
            }
        }

        return false;
    }
}
