<?php

namespace OroB2B\Bundle\TaxBundle\Calculator;

use OroB2B\Bundle\TaxBundle\Entity\TaxRule;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;

interface TaxCalculatorInterface
{
    const ADJUSTMENT_SCALE = 4;

    /**
     * @param string $amount
     * @param TaxRule $taxRule
     * @return ResultElement
     */
    public function calculate($amount, TaxRule $taxRule);
}
