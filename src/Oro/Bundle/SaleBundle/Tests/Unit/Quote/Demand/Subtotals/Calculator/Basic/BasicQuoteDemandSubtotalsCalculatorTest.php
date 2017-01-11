<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Quote\Demand\Subtotals\Calculator\Basic;

use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Quote\Demand\Subtotals\Calculator\Basic\BasicQuoteDemandSubtotalsCalculator;

class BasicQuoteDemandSubtotalsCalculatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TotalProcessorProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $totalProcessorProviderMock;

    /**
     * @var BasicQuoteDemandSubtotalsCalculator
     */
    private $basicQuoteDemandSubtotalsCalculator;

    public function setUp()
    {
        $this->totalProcessorProviderMock = $this->getMockBuilder(TotalProcessorProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->basicQuoteDemandSubtotalsCalculator =
            new BasicQuoteDemandSubtotalsCalculator($this->totalProcessorProviderMock);
    }

    public function testCalculateSubtotals()
    {
        $quoteDemandMock = $this->getMockBuilder(QuoteDemand::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->totalProcessorProviderMock
            ->expects($this->once())
            ->method('getTotalWithSubtotalsAsArray')
            ->with($quoteDemandMock);

        $this->basicQuoteDemandSubtotalsCalculator->calculateSubtotals($quoteDemandMock);
    }
}
