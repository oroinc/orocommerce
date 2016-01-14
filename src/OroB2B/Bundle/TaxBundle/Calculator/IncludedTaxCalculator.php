<?php

namespace OroB2B\Bundle\TaxBundle\Calculator;

use OroB2B\Bundle\TaxBundle\Entity\TaxRule;

class IncludedTaxCalculator extends AbstractRoundingTaxCalculator
{
    /** {@inheritdoc} */
    public function calculate($amount, TaxRule $taxRule)
    {
        $inclTax = $amount;
        $taxRate = abs($taxRule->getTax()->getRate());

        $taxAmount = ($inclTax * $taxRate) / (1 + $taxRate);
        $taxAmountRounded = $this->roundingService->round($taxAmount);

        $exclTax = $inclTax - $taxAmount;

        $adjustment = abs($taxAmount - $taxAmountRounded);

        return $this->returnRoundedResult($inclTax, $exclTax, $taxAmount, $adjustment);
    }
}
