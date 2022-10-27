<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Calculator;

use Oro\Bundle\TaxBundle\Calculator\Calculator;
use Oro\Bundle\TaxBundle\Calculator\TaxCalculatorInterface;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

class CalculatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var TaxationSettingsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $settingsProvider;

    protected function setUp(): void
    {
        $this->settingsProvider = $this->createMock(TaxationSettingsProvider::class);
    }

    public function testTaxIncluded()
    {
        $taxIncl = $this->createMock(TaxCalculatorInterface::class);
        $taxExcl = $this->createMock(TaxCalculatorInterface::class);

        $this->settingsProvider->expects($this->once())
            ->method('isProductPricesIncludeTax')
            ->willReturn(true);
        $taxIncl->expects($this->once())
            ->method('calculate');
        $taxExcl->expects($this->never())
            ->method('calculate');

        $calculator = new Calculator($this->settingsProvider, $taxIncl, $taxExcl);
        $calculator->calculate(0, 0);
    }

    public function testTax()
    {
        $taxIncl = $this->createMock(TaxCalculatorInterface::class);
        $taxExcl = $this->createMock(TaxCalculatorInterface::class);

        $this->settingsProvider->expects($this->once())
            ->method('isProductPricesIncludeTax')
            ->willReturn(false);
        $taxExcl->expects($this->once())
            ->method('calculate');
        $taxIncl->expects($this->never())
            ->method('calculate');

        $calculator = new Calculator($this->settingsProvider, $taxIncl, $taxExcl);
        $calculator->calculate(0, 0);
    }

    public function testGetAmountKeyTaxIncl()
    {
        $taxIncl = $this->createMock(TaxCalculatorInterface::class);
        $taxExcl = $this->createMock(TaxCalculatorInterface::class);

        $this->settingsProvider->expects($this->once())
            ->method('isProductPricesIncludeTax')
            ->willReturn(false);
        $taxExcl->expects($this->once())
            ->method('getAmountKey');
        $taxIncl->expects($this->never())
            ->method('getAmountKey');

        $calculator = new Calculator($this->settingsProvider, $taxIncl, $taxExcl);
        $calculator->getAmountKey();
    }

    public function testGetAmountKey()
    {
        $taxIncl = $this->createMock(TaxCalculatorInterface::class);
        $taxExcl = $this->createMock(TaxCalculatorInterface::class);

        $this->settingsProvider->expects($this->once())
            ->method('isProductPricesIncludeTax')
            ->willReturn(true);
        $taxIncl->expects($this->once())
            ->method('getAmountKey');
        $taxExcl->expects($this->never())
            ->method('getAmountKey');

        $calculator = new Calculator($this->settingsProvider, $taxIncl, $taxExcl);
        $calculator->getAmountKey();
    }
}
