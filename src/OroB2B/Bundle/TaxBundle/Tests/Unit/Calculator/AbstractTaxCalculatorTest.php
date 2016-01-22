<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Calculator;

use Brick\Math\BigNumber;

use OroB2B\Bundle\TaxBundle\Calculator\TaxCalculatorInterface;

abstract class AbstractTaxCalculatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var TaxCalculatorInterface */
    protected $calculator;

    protected function setUp()
    {
        $this->calculator = $this->getCalculator();
    }

    /**
     * @param array $expectedResult
     * @param string $taxableAmount
     * @param string $taxRate
     *
     * @dataProvider calculateDataProvider
     */
    public function testCalculate($expectedResult, $taxableAmount, $taxRate)
    {
        $this->assertEquals(
            $expectedResult,
            array_map(
                function (BigNumber $number) {
                    return (string)$number;
                },
                array_values($this->calculator->calculate($taxableAmount, $taxRate)->getArrayCopy())
            )
        );
    }

    /**
     * @return TaxCalculatorInterface
     */
    abstract protected function getCalculator();
}
