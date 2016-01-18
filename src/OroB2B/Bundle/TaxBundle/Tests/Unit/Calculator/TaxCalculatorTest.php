<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Calculator;

use OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Calculator\TaxCalculator;

class TaxCalculatorTest extends AbstractTaxCalculatorTest
{
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
            'Finney County' => [ResultElement::create('18.53', '17.21', '1.32', '-0.0034'), '17.21', '0.0765'],
            'Fremont County' => [ResultElement::create('61.99', '59.04', '2.95', '0.002'), '59.04', '0.05'],
            'Tulare County' => [ResultElement::create('15.56', '14.41', '1.15', '0.0028'), '14.41', '0.08'],
            'Mclean County' => [ResultElement::create('38.12', '35.88', '2.24', '0.0025'), '35.88', '0.0625'],
            // edge cases
            [ResultElement::create('31.96', '15.98', '15.98', '0'), '15.98', '1'],
            [ResultElement::create('47.94', '15.98', '31.96', '0'), '15.98', '2'],
            [ResultElement::create('31.8', '15.98', '15.82', '0.0002'), '15.98', '0.99'],
            [ResultElement::create('16', '15.98', '0.02', '-0.004'), '15.98', '0.001'],
            [ResultElement::create('16', '15.98', '0.02', '0.004'), '15.98', '0.0015'],
            [ResultElement::create('19.18', '15.98', '3.2', '-0.004'), '15.98', '-0.2'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getCalculator(RoundingServiceInterface $roundingService)
    {
        return new TaxCalculator($roundingService);
    }
}
