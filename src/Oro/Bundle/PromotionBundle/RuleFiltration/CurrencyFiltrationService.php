<?php

namespace Oro\Bundle\PromotionBundle\RuleFiltration;

use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

class CurrencyFiltrationService extends AbstractSkippableFiltrationService
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
        $currentCurrency = $context[ContextDataConverterInterface::CURRENCY] ?? null;
        $filteredOwners = array_values(array_filter(
            $ruleOwners,
            function ($ruleOwner) use ($currentCurrency) {
                return $ruleOwner instanceof PromotionDataInterface
                    && $this->isPromotionForCurrentCurrency($ruleOwner, $currentCurrency);
            }
        ));

        return $this->filtrationService->getFilteredRuleOwners($filteredOwners, $context);
    }

    /**
     * @param PromotionDataInterface $promotion
     * @param string $currentCurrency
     *
     * @return bool
     */
    private function isPromotionForCurrentCurrency(PromotionDataInterface $promotion, $currentCurrency): bool
    {
        $discountConfiguration = $promotion->getDiscountConfiguration();
        $options = $discountConfiguration->getOptions();

        if (!array_key_exists(AbstractDiscount::DISCOUNT_TYPE, $options)) {
            $options[AbstractDiscount::DISCOUNT_TYPE] = null;
        }
        if (!array_key_exists(AbstractDiscount::DISCOUNT_CURRENCY, $options)) {
            $options[AbstractDiscount::DISCOUNT_CURRENCY] = null;
        }

        return $options[AbstractDiscount::DISCOUNT_TYPE] !== DiscountInterface::TYPE_AMOUNT
            || $options[AbstractDiscount::DISCOUNT_CURRENCY] === $currentCurrency;
    }
}
