<?php

namespace Oro\Bundle\SaleBundle\Quote\Demand\Subtotals\Calculator\Decorator;

use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Quote\Demand\Subtotals\Calculator\QuoteDemandSubtotalsCalculatorInterface;

/**
 * Abstract base class for decorators that enhance quote demand subtotal calculation.
 *
 * Provides a foundation for implementing the Decorator pattern to wrap and extend the functionality
 * of {@see QuoteDemandSubtotalsCalculator} implementations, allowing composition of multiple calculation enhancements.
 */
class AbstractQuoteDemandSubtotalsCalculatorDecorator implements QuoteDemandSubtotalsCalculatorInterface
{
    /**
     * @var QuoteDemandSubtotalsCalculatorInterface
     */
    private $quoteDemandSubtotalsCalculator;

    public function __construct(QuoteDemandSubtotalsCalculatorInterface $quoteDemandSubtotalsCalculator)
    {
        $this->quoteDemandSubtotalsCalculator = $quoteDemandSubtotalsCalculator;
    }

    #[\Override]
    public function calculateSubtotals(QuoteDemand $quoteDemand)
    {
        return $this->quoteDemandSubtotalsCalculator->calculateSubtotals($quoteDemand);
    }
}
