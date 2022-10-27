<?php

namespace Oro\Bundle\SaleBundle\Quote\Demand\Subtotals\Calculator\Decorator;

use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Quote\Demand\Subtotals\Calculator\QuoteDemandSubtotalsCalculatorInterface;

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

    /**
     * {@inheritdoc}
     */
    public function calculateSubtotals(QuoteDemand $quoteDemand)
    {
        return $this->quoteDemandSubtotalsCalculator->calculateSubtotals($quoteDemand);
    }
}
