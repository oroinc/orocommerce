<?php

namespace OroB2B\Bundle\TaxBundle\Calculator;

class IncludedTaxCalculator extends AbstractRoundingTaxCalculator
{
    /** {@inheritdoc} */
    public function calculate($amount, $taxRate)
    {
        $inclTax = $amount;
        $taxRate = abs($taxRate);

        $taxAmount = ($inclTax * $taxRate) / (1 + $taxRate);
        $taxAmountRounded = $this->roundingService->round($taxAmount);

        $exclTax = $inclTax - $taxAmount;

        $adjustment = abs($taxAmount - $taxAmountRounded);

        return $this->returnRoundedResult($inclTax, $exclTax, $taxAmount, $adjustment);
    }
}
