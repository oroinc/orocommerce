<?php

namespace OroB2B\Bundle\TaxBundle\Calculator;

use OroB2B\Bundle\TaxBundle\Model\ResultElement;

class TaxCalculator extends AbstractRoundingTaxCalculator
{
    /** {@inheritdoc} */
    public function calculate($amount, $taxRate)
    {
        return new ResultElement();
    }
}
