<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Calculator;

use Oro\Bundle\TaxBundle\Calculator\Calculator;
use Oro\Bundle\TaxBundle\Calculator\TaxCalculatorInterface;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

class CalculatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TaxationSettingsProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $settingsProvider;

    protected function setUp(): void
    {
        $this->settingsProvider = $this->getMockBuilder('Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider')
            ->disableOriginalConstructor()->getMock();
    }

    public function testTaxIncluded()
    {
        /** @var TaxCalculatorInterface|\PHPUnit\Framework\MockObject\MockObject $taxIncl */
        $taxIncl = $this->createMock('Oro\Bundle\TaxBundle\Calculator\TaxCalculatorInterface');

        /** @var TaxCalculatorInterface|\PHPUnit\Framework\MockObject\MockObject $taxExcl */
        $taxExcl = $this->createMock('Oro\Bundle\TaxBundle\Calculator\TaxCalculatorInterface');

        $this->settingsProvider->expects($this->once())->method('isProductPricesIncludeTax')->willReturn(true);
        $taxIncl->expects($this->once())->method('calculate');
        $taxExcl->expects($this->never())->method('calculate');

        $calculator = new Calculator($this->settingsProvider, $taxIncl, $taxExcl);
        $calculator->calculate(0, 0);
    }

    public function testTax()
    {
        /** @var TaxCalculatorInterface|\PHPUnit\Framework\MockObject\MockObject $taxIncl */
        $taxIncl = $this->createMock('Oro\Bundle\TaxBundle\Calculator\TaxCalculatorInterface');

        /** @var TaxCalculatorInterface|\PHPUnit\Framework\MockObject\MockObject $taxExcl */
        $taxExcl = $this->createMock('Oro\Bundle\TaxBundle\Calculator\TaxCalculatorInterface');

        $this->settingsProvider->expects($this->once())->method('isProductPricesIncludeTax')->willReturn(false);
        $taxExcl->expects($this->once())->method('calculate');
        $taxIncl->expects($this->never())->method('calculate');

        $calculator = new Calculator($this->settingsProvider, $taxIncl, $taxExcl);
        $calculator->calculate(0, 0);
    }

    public function testGetAmountKeyTaxIncl()
    {
        /** @var TaxCalculatorInterface|\PHPUnit\Framework\MockObject\MockObject $taxIncl */
        $taxIncl = $this->createMock('Oro\Bundle\TaxBundle\Calculator\TaxCalculatorInterface');

        /** @var TaxCalculatorInterface|\PHPUnit\Framework\MockObject\MockObject $taxExcl */
        $taxExcl = $this->createMock('Oro\Bundle\TaxBundle\Calculator\TaxCalculatorInterface');

        $this->settingsProvider->expects($this->once())->method('isProductPricesIncludeTax')->willReturn(false);
        $taxExcl->expects($this->once())->method('getAmountKey');
        $taxIncl->expects($this->never())->method('getAmountKey');

        $calculator = new Calculator($this->settingsProvider, $taxIncl, $taxExcl);
        $calculator->getAmountKey();
    }

    public function testGetAmountKey()
    {
        /** @var TaxCalculatorInterface|\PHPUnit\Framework\MockObject\MockObject $taxIncl */
        $taxIncl = $this->createMock('Oro\Bundle\TaxBundle\Calculator\TaxCalculatorInterface');

        /** @var TaxCalculatorInterface|\PHPUnit\Framework\MockObject\MockObject $taxExcl */
        $taxExcl = $this->createMock('Oro\Bundle\TaxBundle\Calculator\TaxCalculatorInterface');

        $this->settingsProvider->expects($this->once())->method('isProductPricesIncludeTax')->willReturn(true);
        $taxIncl->expects($this->once())->method('getAmountKey');
        $taxExcl->expects($this->never())->method('getAmountKey');

        $calculator = new Calculator($this->settingsProvider, $taxIncl, $taxExcl);
        $calculator->getAmountKey();
    }
}
