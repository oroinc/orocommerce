<?php

namespace Oro\Bundle\TaxBundle\Calculator;

use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Component\Math\BigDecimal;

/**
 * ($exclTax * taxRate) + $exclTax
 */
class TaxCalculator extends AbstractTaxCalculator
{
    /**
     * {@inheritdoc}
     */
    protected function doCalculate(string $amount, string $taxRate): ResultElement
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
