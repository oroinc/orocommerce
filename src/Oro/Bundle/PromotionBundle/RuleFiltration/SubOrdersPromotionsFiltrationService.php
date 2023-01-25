<?php

namespace Oro\Bundle\PromotionBundle\RuleFiltration;

use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Context\OrderContextDataConverter;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

/**
 * Filter promotions if Order has subOrders.
 */
class SubOrdersPromotionsFiltrationService extends AbstractSkippableFiltrationService
{
    private RuleFiltrationServiceInterface $filtrationService;
    private ContextDataConverterInterface $contextDataConverter;

    public function __construct(
        RuleFiltrationServiceInterface $filtrationService,
        ContextDataConverterInterface $contextDataConverter
    ) {
        $this->filtrationService = $filtrationService;
        $this->contextDataConverter = $contextDataConverter;
    }

    /**
     * Promotions should be filters for each subOrder separately.
     *
     * @param array $ruleOwners
     * @param array $context
     * @return array|\Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface[]
     */
    protected function filterRuleOwners(array $ruleOwners, array $context)
    {
        $subEntities = $context[OrderContextDataConverter::SUB_ORDERS] ?? [];

        if (!empty($subEntities)) {
            $promotions = [];
            foreach ($subEntities as $entity) {
                $contextData = $this->contextDataConverter->getContextData($entity);

                $skipFilters = $contextData[AbstractSkippableFiltrationService::SKIP_FILTERS_KEY] ?? [];
                $contextData[AbstractSkippableFiltrationService::SKIP_FILTERS_KEY] = $skipFilters;

                $entityPromotions = $this->filtrationService->getFilteredRuleOwners($ruleOwners, $contextData);

                /** @var PromotionDataInterface $promotion */
                foreach ($entityPromotions as $promotion) {
                    $promotions[$promotion->getId()] = $promotion;
                }
            }

            return array_values($promotions);
        }
        return $this->filtrationService->getFilteredRuleOwners($ruleOwners, $context);
    }
}
