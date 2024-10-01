<?php

namespace Oro\Bundle\PromotionBundle\RuleFiltration;

use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Discount\ShippingDiscount;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

/**
 * Filters out promotions for shipping discount if promotion's options not fit shipping method
 * and shipping method type from context.
 */
class ShippingFiltrationService extends AbstractSkippableFiltrationService
{
    public function __construct(
        private RuleFiltrationServiceInterface $baseFiltrationService
    ) {
    }

    #[\Override]
    protected function filterRuleOwners(array $ruleOwners, array $context): array
    {
        $filteredRuleOwners = $this->filterShippingRuleOwners(
            $ruleOwners,
            $context[ContextDataConverterInterface::SHIPPING_METHOD] ?? null,
            $context[ContextDataConverterInterface::SHIPPING_METHOD_TYPE] ?? null
        );

        return $this->baseFiltrationService->getFilteredRuleOwners($filteredRuleOwners, $context);
    }

    private function filterShippingRuleOwners(
        array $ruleOwners,
        ?string $shippingMethod,
        ?string $shippingMethodType
    ): array {
        $filteredRuleOwners = [];
        foreach ($ruleOwners as $ruleOwner) {
            if (!$ruleOwner instanceof PromotionDataInterface) {
                continue;
            }

            $discountConfiguration = $ruleOwner->getDiscountConfiguration();
            if ($discountConfiguration->getType() !== ShippingDiscount::NAME
                || $this->isShippingOptionsMatched($discountConfiguration, $shippingMethod, $shippingMethodType)
            ) {
                $filteredRuleOwners[] = $ruleOwner;
            }
        }

        return $filteredRuleOwners;
    }

    private function isShippingOptionsMatched(
        DiscountConfiguration $discountConfiguration,
        ?string $shippingMethod,
        ?string $shippingMethodType
    ): bool {
        $opt = $discountConfiguration->getOptions();

        return
            $shippingMethod === $opt[ShippingDiscount::SHIPPING_OPTIONS][ShippingDiscount::SHIPPING_METHOD]
            && $shippingMethodType === $opt[ShippingDiscount::SHIPPING_OPTIONS][ShippingDiscount::SHIPPING_METHOD_TYPE];
    }
}
