<?php

namespace Oro\Bundle\PromotionBundle\RuleFiltration;

use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Discount\ShippingDiscount;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

/**
 * It filter out promotions for shipping discount if promotion's options not fit shipping method and shipping method
 * type from context.
 */
class ShippingFiltrationService extends AbstractSkippableFiltrationService
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
    protected function filterRuleOwners(array $ruleOwners, array $context): array
    {
        $shippingMethod = $context[ContextDataConverterInterface::SHIPPING_METHOD] ?? null;
        $shippingMethodType = $context[ContextDataConverterInterface::SHIPPING_METHOD_TYPE] ?? null;

        $filteredOwners = array_values(
            array_filter(
                $ruleOwners,
                function ($ruleOwner) use ($shippingMethod, $shippingMethodType) {
                    if (!$ruleOwner instanceof PromotionDataInterface) {
                        return false;
                    }

                    if ($ruleOwner->getDiscountConfiguration()->getType() !== ShippingDiscount::NAME) {
                        return true;
                    }

                    return $this->isShippingOptionsMatched($ruleOwner, $shippingMethod, $shippingMethodType);
                }
            )
        );

        return $this->filtrationService->getFilteredRuleOwners($filteredOwners, $context);
    }

    /**
     * @param PromotionDataInterface $promotion
     * @param string|null            $shippingMethod
     * @param string|null            $shippingMethodType
     *
     * @return bool
     */
    private function isShippingOptionsMatched(PromotionDataInterface $promotion, $shippingMethod, $shippingMethodType)
    {
        $discountOptions = $promotion->getDiscountConfiguration()->getOptions();

        $optionsMethod = $discountOptions[ShippingDiscount::SHIPPING_OPTIONS][ShippingDiscount::SHIPPING_METHOD];
        $optionsType = $discountOptions[ShippingDiscount::SHIPPING_OPTIONS][ShippingDiscount::SHIPPING_METHOD_TYPE];

        return $shippingMethod === $optionsMethod && $shippingMethodType === $optionsType;
    }
}
