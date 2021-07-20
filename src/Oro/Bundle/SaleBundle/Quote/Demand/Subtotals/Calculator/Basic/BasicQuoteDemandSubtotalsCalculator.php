<?php

namespace Oro\Bundle\SaleBundle\Quote\Demand\Subtotals\Calculator\Basic;

use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Quote\Demand\Subtotals\Calculator\QuoteDemandSubtotalsCalculatorInterface;

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

    /**
     * {@inheritdoc}
     */
    public function calculateSubtotals(QuoteDemand $quoteDemand)
    {
        return $this->totalProcessorProvider->getTotalWithSubtotalsAsArray($quoteDemand);
    }
}
