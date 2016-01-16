<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Calculator;

use OroB2B\Bundle\TaxBundle\Calculator\IncludedTaxCalculator;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Rounding\TaxRoundingService;

class IncludedTaxCalculatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var IncludedTaxCalculator */
    protected $calculator;

    protected function setUp()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|TaxRoundingService $roundingService */
        $roundingService = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Rounding\TaxRoundingService')
            ->disableOriginalConstructor()->getMock();
        $roundingService->expects($this->any())->method('round')->willReturnCallback(
            function ($value, $precision, $roundType) {
                return (string)round(
                    $value,
                    $precision ?: TaxRoundingService::TAX_PRECISION,
                    $roundType === TaxRoundingService::HALF_DOWN ? PHP_ROUND_HALF_DOWN : PHP_ROUND_HALF_UP
                );
            }
        );
        $this->calculator = new IncludedTaxCalculator($roundingService);
    }

    /**
     * @param ResultElement $expectedResult
     * @param string $taxableAmount
     * @param string $taxRate
     *
     * @dataProvider calculateDataProvider
     */
    public function testCalculate(ResultElement $expectedResult, $taxableAmount, $taxRate)
    {
        $this->assertEquals(
            $expectedResult,
            $this->calculator->calculate($taxableAmount, $taxRate)
        );
    }

    /**
     * @return array
     *
     * @link http://salestax.avalara.com/
     */
    public function calculateDataProvider()
    {
        return [
            // use cases
            'Finney County' => [ResultElement::create('17.21', '15.99', '1.22', '0.003'), '17.21', '0.0765'],
            'Fremont County' => [ResultElement::create('59.04', '56.23', '2.81', '0.0014'), '59.04', '0.05'],
            'Tulare County' => [ResultElement::create('14.41', '13.34', '1.07', '-0.0026'), '14.41', '0.08'],
            'Mclean County' => [ResultElement::create('35.88', '33.77', '2.11', '0.0006'), '35.88', '0.0625'],

            // edge cases
            [ResultElement::create('15.98', '7.99', '7.99', '0'), '15.98', '1'],
            [ResultElement::create('15.98', '5.33', '10.65', '0.0033'), '15.98', '2'],
            [ResultElement::create('15.98', '8.03', '7.95', '-0.0002'), '15.98', '0.99'],
            [ResultElement::create('15.98', '15.96', '0.02', '-0.004'), '15.98', '0.001'],
            [ResultElement::create('15.98', '15.96', '0.02', '0.0039'), '15.98', '0.0015'],
            [ResultElement::create('15.98', '13.32', '2.66', '0.0033'), '15.98', '-0.2'],
        ];
    }
}
