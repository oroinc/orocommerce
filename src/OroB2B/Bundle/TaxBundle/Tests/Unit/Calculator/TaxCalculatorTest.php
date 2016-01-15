<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Calculator;

use OroB2B\Bundle\TaxBundle\Entity\Tax;
use OroB2B\Bundle\TaxBundle\Entity\TaxRule;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Calculator\TaxCalculator;
use OroB2B\Bundle\TaxBundle\Rounding\TaxRoundingService;

class TaxCalculatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var TaxCalculator */
    protected $calculator;

    protected function setUp()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|TaxRoundingService $roundingService */
        $roundingService = $this
            ->getMockBuilder('OroB2B\Bundle\TaxBundle\Rounding\TaxRoundingService')
            ->disableOriginalConstructor()
            ->getMock();

        $roundingService
            ->expects($this->any())
            ->method('round')
            ->willReturnCallback(
                function ($value, $precision, $roundType) {
                    return (string)round(
                        $value,
                        $precision ?: TaxRoundingService::TAX_PRECISION,
                        $roundType === TaxRoundingService::HALF_DOWN ? PHP_ROUND_HALF_DOWN : PHP_ROUND_HALF_UP
                    );
                }
            );
        $this->calculator = new TaxCalculator($roundingService);
    }

    /**
     * @param ResultElement $expectedResult
     * @param string $taxableAmount
     * @param $taxRule TaxRule
     *
     * @dataProvider calculateDataProvider
     */
    public function testCalculate(ResultElement $expectedResult, $taxableAmount, TaxRule $taxRule)
    {
        $this->assertEquals(
            $expectedResult,
            $this->calculator->calculate($taxableAmount, $taxRule)
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
            'Finney County' => [
                ResultElement::create('18.53', '17.21', '1.32', '-0.0034'),
                '17.21',
                $this->createTaxRule('0.0765'),
            ],
            'Fremont County' => [
                ResultElement::create('61.99', '59.04', '2.95', '0.002'),
                '59.04',
                $this->createTaxRule('0.05'),
            ],
            'Tulare County' => [
                ResultElement::create('15.56', '14.41', '1.15', '0.0028'),
                '14.41',
                $this->createTaxRule('0.08'),
            ],
            'Mclean County' => [
                ResultElement::create('38.12', '35.88', '2.24', '0.0025'),
                '35.88',
                $this->createTaxRule('0.0625'),
            ],

            // edge cases
            [
                ResultElement::create('31.96', '15.98', '15.98', '0'),
                '15.98',
                $this->createTaxRule('1'),
            ],
            [
                ResultElement::create('47.94', '15.98', '31.96', '0'),
                '15.98',
                $this->createTaxRule('2'),
            ],
            [
                ResultElement::create('31.8', '15.98', '15.82', '0.0002'),
                '15.98',
                $this->createTaxRule('0.99'),
            ],
            [
                ResultElement::create('16', '15.98', '0.02', '-0.004'),
                '15.98',
                $this->createTaxRule('0.001'),
            ],
            [
                ResultElement::create('16', '15.98', '0.02', '0.004'),
                '15.98',
                $this->createTaxRule('0.0015'),
            ],
            [
                ResultElement::create('19.18', '15.98', '3.2', '-0.004'),
                '15.98',
                $this->createTaxRule('-0.2'),
            ],
        ];
    }

    /**
     * @param int $taxRate
     * @return TaxRule
     */
    protected function createTaxRule($taxRate)
    {
        $taxRule = new TaxRule();
        $tax = new Tax();
        $tax->setRate($taxRate);
        $taxRule->setTax($tax);

        return $taxRule;
    }
}
