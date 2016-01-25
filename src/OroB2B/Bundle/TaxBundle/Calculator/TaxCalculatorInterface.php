<?php

namespace OroB2B\Bundle\TaxBundle\Calculator;

use OroB2B\Bundle\TaxBundle\Model\ResultElement;

interface TaxCalculatorInterface
{
    const CALCULATION_SCALE = 9;
    const SCALE = 2;

    /**
     * @param string $amount
     * @param string $taxRate
     * @return ResultElement
     */
    public function calculate($amount, $taxRate);
}
