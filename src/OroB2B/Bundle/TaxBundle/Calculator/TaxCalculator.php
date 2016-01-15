<?php

namespace OroB2B\Bundle\TaxBundle\Calculator;

use OroB2B\Bundle\TaxBundle\Entity\TaxRule;

class TaxCalculator extends AbstractRoundingTaxCalculator
{
    /** {@inheritdoc} */
    public function calculate($amount, TaxRule $taxRule)
    {
        $taxRate = abs($taxRule->getTax()->getRate());

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
