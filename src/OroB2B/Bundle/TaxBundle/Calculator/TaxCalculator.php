<?php

namespace OroB2B\Bundle\TaxBundle\Calculator;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

use OroB2B\Bundle\TaxBundle\Model\ResultElement;

class TaxCalculator implements TaxCalculatorInterface
{
    /** {@inheritdoc} */
    public function calculate($amount, $taxRate)
    {
        $exclTax = BigDecimal::of($amount);
        $taxRate = BigDecimal::of($taxRate)->abs();

        $taxAmount = $exclTax->multipliedBy($taxRate);
        $inclTax = $exclTax->plus($taxAmount);

        $taxAmountRounded = $taxAmount->toScale(self::SCALE, RoundingMode::UP);

        $adjustment = $taxAmountRounded->minus($taxAmount);

        return ResultElement::create($inclTax, $exclTax, $taxAmount, $adjustment);
    }
}
