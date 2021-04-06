<?php

namespace Oro\Bundle\PromotionBundle\Resolver;

use Brick\Math\BigDecimal;
use Oro\Bundle\TaxBundle\Calculator\TaxCalculatorInterface;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TaxBundle\Resolver\CalculateAdjustmentTrait;
use Oro\Bundle\TaxBundle\Resolver\RoundingResolver;
use Oro\Bundle\TaxBundle\Resolver\TotalResolver;
use Oro\Component\Math\RoundingMode;

/**
 * Recalculates Tax ResultElement in case when option Calculate Taxes After Promotions is enabled
 */
class TotalAfterDiscountsResolver extends TotalResolver
{
    use CalculateAdjustmentTrait;

    private TaxCalculatorInterface $calculator;

    public function __construct(
        TaxationSettingsProvider $settingsProvider,
        RoundingResolver $roundingResolver,
        TaxCalculatorInterface $calculator
    ) {
        parent::__construct($settingsProvider, $roundingResolver);

        $this->calculator = $calculator;
    }

    protected function mergeAdditionalData(Taxable $taxable, ResultElement $target): ResultElement
    {
        if ($this->settingsProvider->isCalculateAfterPromotionsEnabled()) {
            return $this->calculateResultElementAfterPromotions($taxable->getAmount(), $target);
        }

        return $target;
    }

    protected function calculateResultElementAfterPromotions(
        BigDecimal $amountWithDiscount,
        ResultElement $target
    ): ResultElement {
        if (BigDecimal::of($target->getTaxAmount())->isZero()) {
            return $target;
        }

        $taxRate = BigDecimal::of($target->getExcludingTax())
            ->dividedBy(
                $target->getTaxAmount(),
                TaxationSettingsProvider::CALCULATION_SCALE,
                RoundingMode::HALF_UP
            )
            ->dividedBy(
                100,
                TaxationSettingsProvider::CALCULATION_SCALE,
                RoundingMode::HALF_UP
            );

        $newResultElement = $this->calculator->calculate($amountWithDiscount, $taxRate);
        $this->calculateAdjustment($newResultElement);

        return $newResultElement;
    }
}
