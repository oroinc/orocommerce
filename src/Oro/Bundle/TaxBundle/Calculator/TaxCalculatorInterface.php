<?php

namespace Oro\Bundle\TaxBundle\Calculator;

use Oro\Bundle\TaxBundle\Model\ResultElement;

/**
 * Defines the contract for tax calculation strategies.
 *
 * Implementations of this interface provide different approaches to calculating taxes,
 * such as calculating tax on prices that include tax versus prices that exclude tax.
 * The calculator determines the tax amount, amounts with and without tax, and any rounding adjustments.
 */
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
