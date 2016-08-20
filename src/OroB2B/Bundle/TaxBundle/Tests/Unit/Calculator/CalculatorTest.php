<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Calculator;

use Oro\Bundle\TaxBundle\Calculator\Calculator;
use Oro\Bundle\TaxBundle\Calculator\TaxCalculatorInterface;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

class CalculatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TaxationSettingsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $settingsProvider;

    protected function setUp()
    {
        $this->settingsProvider = $this->getMockBuilder('Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider')
            ->disableOriginalConstructor()->getMock();
    }

    public function testTaxIncluded()
    {
        /** @var TaxCalculatorInterface|\PHPUnit_Framework_MockObject_MockObject $taxIncl */
        $taxIncl = $this->getMock('Oro\Bundle\TaxBundle\Calculator\TaxCalculatorInterface');

        /** @var TaxCalculatorInterface|\PHPUnit_Framework_MockObject_MockObject $taxExcl */
        $taxExcl = $this->getMock('Oro\Bundle\TaxBundle\Calculator\TaxCalculatorInterface');

        $this->settingsProvider->expects($this->once())->method('isProductPricesIncludeTax')->willReturn(true);
        $taxIncl->expects($this->once())->method('calculate');
        $taxExcl->expects($this->never())->method('calculate');

        $calculator = new Calculator($this->settingsProvider, $taxIncl, $taxExcl);
        $calculator->calculate(0, 0);
    }

    public function testTax()
    {
        /** @var TaxCalculatorInterface|\PHPUnit_Framework_MockObject_MockObject $taxIncl */
        $taxIncl = $this->getMock('Oro\Bundle\TaxBundle\Calculator\TaxCalculatorInterface');

        /** @var TaxCalculatorInterface|\PHPUnit_Framework_MockObject_MockObject $taxExcl */
        $taxExcl = $this->getMock('Oro\Bundle\TaxBundle\Calculator\TaxCalculatorInterface');

        $this->settingsProvider->expects($this->once())->method('isProductPricesIncludeTax')->willReturn(false);
        $taxExcl->expects($this->once())->method('calculate');
        $taxIncl->expects($this->never())->method('calculate');

        $calculator = new Calculator($this->settingsProvider, $taxIncl, $taxExcl);
        $calculator->calculate(0, 0);
    }

    public function testGetAmountKeyTaxIncl()
    {
        /** @var TaxCalculatorInterface|\PHPUnit_Framework_MockObject_MockObject $taxIncl */
        $taxIncl = $this->getMock('Oro\Bundle\TaxBundle\Calculator\TaxCalculatorInterface');

        /** @var TaxCalculatorInterface|\PHPUnit_Framework_MockObject_MockObject $taxExcl */
        $taxExcl = $this->getMock('Oro\Bundle\TaxBundle\Calculator\TaxCalculatorInterface');

        $this->settingsProvider->expects($this->once())->method('isProductPricesIncludeTax')->willReturn(false);
        $taxExcl->expects($this->once())->method('getAmountKey');
        $taxIncl->expects($this->never())->method('getAmountKey');

        $calculator = new Calculator($this->settingsProvider, $taxIncl, $taxExcl);
        $calculator->getAmountKey();
    }

    public function testGetAmountKey()
    {
        /** @var TaxCalculatorInterface|\PHPUnit_Framework_MockObject_MockObject $taxIncl */
        $taxIncl = $this->getMock('Oro\Bundle\TaxBundle\Calculator\TaxCalculatorInterface');

        /** @var TaxCalculatorInterface|\PHPUnit_Framework_MockObject_MockObject $taxExcl */
        $taxExcl = $this->getMock('Oro\Bundle\TaxBundle\Calculator\TaxCalculatorInterface');

        $this->settingsProvider->expects($this->once())->method('isProductPricesIncludeTax')->willReturn(true);
        $taxIncl->expects($this->once())->method('getAmountKey');
        $taxExcl->expects($this->never())->method('getAmountKey');

        $calculator = new Calculator($this->settingsProvider, $taxIncl, $taxExcl);
        $calculator->getAmountKey();
    }
}
