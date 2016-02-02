<?php

namespace OroB2B\Bundle\TaxBundle\Calculator;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

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
            ->dividedBy($taxRate->plus(1), $this->getDivisionScale(), RoundingMode::HALF_UP);

        $exclTax = $inclTax->minus($taxAmount);

        $exclTaxRounded = $exclTax->toScale(TaxationSettingsProvider::SCALE, RoundingMode::HALF_UP);

        $adjustment = $exclTax->minus($exclTaxRounded);

        return ResultElement::create($inclTax, $exclTax, $taxAmount, $adjustment);
    }

    /**
     * Division scale depends on calculation scale
     * For scale = 2 we use 3rd number to scale and fourth position to divide
     * Shift rounding to scale + 1 position for half_up rounding + 1 position for division scale
     *
     * @return int
     */
    protected function getDivisionScale()
    {
        return TaxationSettingsProvider::SCALE + 2;
    }
}
