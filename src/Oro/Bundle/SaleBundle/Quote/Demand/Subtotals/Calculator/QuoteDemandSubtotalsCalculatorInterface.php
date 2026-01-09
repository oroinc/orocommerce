<?php

namespace Oro\Bundle\SaleBundle\Quote\Demand\Subtotals\Calculator;

use Oro\Bundle\SaleBundle\Entity\QuoteDemand;

/**
 * Defines the contract for calculating quote demand subtotals and totals.
 *
 * Implementations compute financial metrics for quote demands, including subtotals, taxes, shipping costs,
 * and other financial information needed for quote presentation and processing.
 */
interface QuoteDemandSubtotalsCalculatorInterface
{
    /**
     * @param QuoteDemand $quoteDemand
     *
     * @return array
     */
    public function calculateSubtotals(QuoteDemand $quoteDemand);
}
