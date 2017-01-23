<?php

namespace Oro\Bundle\SaleBundle\Quote\Demand\Subtotals\Calculator;

use Oro\Bundle\SaleBundle\Entity\QuoteDemand;

interface QuoteDemandSubtotalsCalculatorInterface
{
    /**
     * @param QuoteDemand $quoteDemand
     *
     * @return array
     */
    public function calculateSubtotals(QuoteDemand $quoteDemand);
}
