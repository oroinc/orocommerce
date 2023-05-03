<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Quote\Demand\Subtotals\Calculator\Basic;

use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Quote\Demand\Subtotals\Calculator\Basic\BasicQuoteDemandSubtotalsCalculator;

class BasicQuoteDemandSubtotalsCalculatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var TotalProcessorProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $totalProcessorProvider;

    /** @var BasicQuoteDemandSubtotalsCalculator */
    private $basicQuoteDemandSubtotalsCalculator;

    protected function setUp(): void
    {
        $this->totalProcessorProvider = $this->createMock(TotalProcessorProvider::class);

        $this->basicQuoteDemandSubtotalsCalculator = new BasicQuoteDemandSubtotalsCalculator(
            $this->totalProcessorProvider
        );
    }

    public function testCalculateSubtotals()
    {
        $quoteDemand = $this->createMock(QuoteDemand::class);

        $this->totalProcessorProvider->expects($this->once())
            ->method('getTotalWithSubtotalsAsArray')
            ->with($quoteDemand);

        $this->basicQuoteDemandSubtotalsCalculator->calculateSubtotals($quoteDemand);
    }
}
