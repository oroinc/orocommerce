<?php

namespace Oro\Bundle\TaxBundle\Calculator;

use Brick\Math\BigDecimal;

use Oro\Bundle\TaxBundle\Model\ResultElement;

class TaxCalculator implements TaxCalculatorInterface
{
    /** {@inheritdoc} */
    public function calculate($amount, $taxRate)
    {
        $exclTax = BigDecimal::of($amount);
        $taxRate = BigDecimal::of($taxRate)->abs();

        $taxAmount = $exclTax->multipliedBy($taxRate);
        $inclTax = $exclTax->plus($taxAmount);

        return ResultElement::create($inclTax, $exclTax, $taxAmount);
    }

    /** {@inheritdoc} */
    public function getAmountKey()
    {
        return ResultElement::EXCLUDING_TAX;
    }
}
