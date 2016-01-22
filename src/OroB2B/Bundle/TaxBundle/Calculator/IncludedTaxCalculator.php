<?php

namespace OroB2B\Bundle\TaxBundle\Calculator;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

use OroB2B\Bundle\TaxBundle\Model\ResultElement;

/**
 * (inclTax * taxRate) / (1 + taxRate)
 */
class IncludedTaxCalculator implements TaxCalculatorInterface
{
    /** {@inheritdoc} */
    public function calculate($amount, $taxRate)
    {
        $inclTax = BigDecimal::of($amount);
        $taxRate = BigDecimal::of($taxRate)->abs();

        $taxAmount = $inclTax
            ->multipliedBy($taxRate)
            ->dividedBy($taxRate->plus(1), self::CALCULATION_SCALE, RoundingMode::UP);

        $exclTax = $inclTax->minus($taxAmount);

        $exclTaxRounded = $exclTax->toScale(self::SCALE, RoundingMode::UP);

        $adjustment = $exclTaxRounded->minus($exclTax);

        return ResultElement::create($inclTax, $exclTax, $taxAmount, $adjustment);
    }
}
