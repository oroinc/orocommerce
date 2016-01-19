<?php

namespace OroB2B\Bundle\TaxBundle\Calculator;

class TaxCalculator extends AbstractRoundingTaxCalculator
{
    /** {@inheritdoc} */
    public function calculate($amount, $taxRate)
    {
        $taxRate = abs($taxRate);

        $taxAmount = $amount * $taxRate;
        $taxAmountRounded = $this->roundingService->round($taxAmount);
        $inclTax = $amount + $taxAmount;
        $exclTax = $amount;
        $adjustment = $taxAmount - $taxAmountRounded;

        return $this->returnRoundedResult(
            $inclTax,
            $exclTax,
            $taxAmount,
            $adjustment
        );
    }
}
