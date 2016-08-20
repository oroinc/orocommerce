<?php

namespace Oro\Bundle\TaxBundle\Calculator;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

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
            ->dividedBy($taxRate->plus(1), TaxationSettingsProvider::CALCULATION_SCALE, RoundingMode::HALF_UP);

        $exclTax = $inclTax->minus($taxAmount);

        return ResultElement::create($inclTax, $exclTax, $taxAmount);
    }

    /** {@inheritdoc} */
    public function getAmountKey()
    {
        return ResultElement::INCLUDING_TAX;
    }
}
