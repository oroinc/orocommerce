<?php

namespace Oro\Bundle\PromotionBundle\RuleFiltration;

use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Context\OrderContextDataConverter;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

/**
 * Filters out promotions when an order has sub orders.
 * In this case promotions should be separately filtered for each sub order.
 */
class SubOrdersFiltrationService extends AbstractSkippableFiltrationService
{
    public function __construct(
        private RuleFiltrationServiceInterface $baseFiltrationService,
        private ContextDataConverterInterface $contextDataConverter
    ) {
    }

    #[\Override]
    protected function filterRuleOwners(array $ruleOwners, array $context): array
    {
        $subOrders = $context[OrderContextDataConverter::SUB_ORDERS] ?? [];
        if (!empty($subOrders)) {
            return $this->getFilteredRuleOwnersForSubOrders($ruleOwners, $subOrders);
        }

        return $this->baseFiltrationService->getFilteredRuleOwners($ruleOwners, $context);
    }

    private function getFilteredRuleOwnersForSubOrders(array $ruleOwners, array $subOrders): array
    {
        $filteredRuleOwners = [];
        foreach ($subOrders as $subOrder) {
            $subOrderContext = $this->contextDataConverter->getContextData($subOrder);
            $subOrderContext[AbstractSkippableFiltrationService::SKIP_FILTERS_KEY] =
                $subOrderContext[AbstractSkippableFiltrationService::SKIP_FILTERS_KEY] ?? [];

            $subOrderRuleOwners = $this->baseFiltrationService->getFilteredRuleOwners($ruleOwners, $subOrderContext);
            foreach ($subOrderRuleOwners as $ruleOwner) {
                $filteredRuleOwners[$ruleOwner->getId()] = $ruleOwner;
            }
        }

        return array_values($filteredRuleOwners);
    }
}
