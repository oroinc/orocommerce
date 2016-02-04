<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Calculator;

use OroB2B\Bundle\TaxBundle\Calculator\IncludedTaxCalculator;

class IncludedTaxCalculatorTest extends AbstractTaxCalculatorTest
{
    /**
     * @return array
     *
     * @link http://salestax.avalara.com/
     */
    public function calculateDataProvider()
    {
        return [
            // use cases
            'Finney County' => [['17.21', '15.9870', '1.2230', '-0.0030'], '17.21', '0.0765'],
            'Fremont County' => [['59.04', '56.2286', '2.8114', '-0.0014'], '59.04', '0.05'],
            'Tulare County' => [['14.41', '13.3426', '1.0674', '0.0026'], '14.41', '0.08'],
            'Mclean County' => [['35.88', '33.7694', '2.1106', '-0.0006'], '35.88', '0.0625'],

            // edge cases
            [['15.98', '7.99', '7.99', '0'], '15.98', '1'],
            [['15.98', '5.3267', '10.6533', '-0.0033'], '15.98', '2'],
            [['15.98', '8.0302', '7.9498', '0.0002'], '15.98', '0.99'],
            [['15.98', '15.9640', '0.0160', '0.0040'], '15.98', '0.001'],
            [['15.98', '15.9561', '0.0239', '-0.0039'], '15.98', '0.0015'],
            [['15.98', '13.3167', '2.6633', '-0.0033'], '15.98', '-0.2'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getCalculator()
    {
        return new IncludedTaxCalculator();
    }
}
