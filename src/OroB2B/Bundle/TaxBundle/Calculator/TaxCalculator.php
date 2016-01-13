<?php

namespace OroB2B\Bundle\TaxBundle\Calculator;

use OroB2B\Bundle\TaxBundle\Entity\TaxRule;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;

class TaxCalculator extends AbstractRoundingTaxCalculator
{
    /** {@inheritdoc} */
    public function calculate($amount, TaxRule $taxRule)
    {
        return new ResultElement();
    }
}
