<?php

namespace Oro\Bundle\SaleBundle\Quote\Demand\Subtotals\Calculator\Basic;

use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Quote\Demand\Subtotals\Calculator\QuoteDemandSubtotalsCalculatorInterface;

/**
 * Calculates subtotals and totals for quote demands using the pricing bundle's total processor.
 *
 * Provides basic calculation functionality for quote demand financial metrics, delegating
 * to the {@see TotalProcessorProvider} to compute totals and subtotals based on line items and pricing information.
 */
class BasicQuoteDemandSubtotalsCalculator implements QuoteDemandSubtotalsCalculatorInterface
{
    /**
     * @var TotalProcessorProvider
     */
    private $totalProcessorProvider;

    public function __construct(TotalProcessorProvider $totalProcessorProvider)
    {
        $this->totalProcessorProvider = $totalProcessorProvider;
    }

    #[\Override]
    public function calculateSubtotals(QuoteDemand $quoteDemand)
    {
        return $this->totalProcessorProvider->getTotalWithSubtotalsAsArray($quoteDemand);
    }
}
