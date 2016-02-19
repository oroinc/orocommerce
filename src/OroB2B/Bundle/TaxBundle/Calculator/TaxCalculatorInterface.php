<?php

namespace OroB2B\Bundle\TaxBundle\Calculator;

use OroB2B\Bundle\TaxBundle\Model\ResultElement;

interface TaxCalculatorInterface
{
    /**
     * @param string $amount
     * @param string $taxRate
     * @return ResultElement|array
     *      includingTax - amount with tax
     *      excludingTax - amount without tax
     *      taxAmount    - tax amount
     *      adjustment   - adjustment, negative value when taxAmount rounded up, positive value if rounded down
     */
    public function calculate($amount, $taxRate);

    /**
     * @return string
     */
    public function getAmountKey();
}
