<?php

namespace Oro\Bundle\PromotionBundle\RuleFiltration;

use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

class CurrencyFiltrationService implements RuleFiltrationServiceInterface
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
        $currentCurrency = $context['currency'];
        $filteredOwners = array_values(array_filter(
            $ruleOwners,
            function ($ruleOwner) use ($currentCurrency) {
                return $ruleOwner instanceof Promotion
                    && $this->isPromotionForCurrentCurrency($ruleOwner, $currentCurrency);
            }
        ));

        return $this->filtrationService->getFilteredRuleOwners($filteredOwners, $context);
    }

    /**
     * @param Promotion $promotion
     * @param string $currentCurrency
     *
     * @return bool
     */
    private function isPromotionForCurrentCurrency(Promotion $promotion, $currentCurrency): bool
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
